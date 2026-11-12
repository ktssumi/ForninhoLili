<?php
// API própria da loja. Dados persistidos em storage/dados.json no servidor.
 session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$config = require __DIR__ . '/api-config.php';
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) { mkdir($storageDir, 0750, true); }
$file = $storageDir . '/dados.json';
if (!file_exists($file)) { file_put_contents($file, json_encode(new stdClass())); }
function out($data, $status=200) { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function readData($file) { $fp=fopen($file,'c+'); flock($fp, LOCK_SH); $raw=stream_get_contents($fp); flock($fp,LOCK_UN); fclose($fp); $d=json_decode($raw ?: '{}',true); return is_array($d)?$d:[]; }
function writeData($file,$data) { $fp=fopen($file,'c+'); flock($fp,LOCK_EX); ftruncate($fp,0); rewind($fp); fwrite($fp,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)); fflush($fp); flock($fp,LOCK_UN); fclose($fp); }
function logged(){ return !empty($_SESSION['dona']) && $_SESSION['dona']===true; }
$input=json_decode(file_get_contents('php://input'),true) ?: [];
$action=$input['action'] ?? '';
if ($action==='login') {
  $email=strtolower(trim($input['email']??'')); $pass=(string)($input['password']??'');
  $saved=readData($file); $passwordHash=$saved['auth']['password_hash']??$config['password_hash'];
  if ($email!==strtolower($config['email']) || !password_verify($pass,$passwordHash)) out(['error'=>'E-mail ou senha incorretos.'],401);
  session_regenerate_id(true); $_SESSION['dona']=true; out(['user'=>['email'=>$config['email']]]);
}
if ($action==='logout') { $_SESSION=[]; session_destroy(); out(['ok'=>true]); }
if ($action==='me') out(['user'=>logged()?['email'=>$config['email']]:null]);
if ($action==='change_password') {
  if (!logged()) out(['error'=>'Faça login novamente para alterar a senha.'],401);
  $saved=readData($file); $currentHash=$saved['auth']['password_hash']??$config['password_hash'];
  $current=(string)($input['current_password']??''); $new=(string)($input['new_password']??'');
  if (!password_verify($current,$currentHash)) out(['error'=>'A senha atual está incorreta.'],400);
  if (strlen($new)<8) out(['error'=>'A nova senha precisa ter pelo menos 8 caracteres.'],400);
  if (hash_equals($current,$new)) out(['error'=>'Escolha uma senha diferente da atual.'],400);
  $saved['auth']=$saved['auth']??[]; $saved['auth']['password_hash']=password_hash($new,PASSWORD_DEFAULT);
  writeData($file,$saved); out(['ok'=>true,'message'=>'Senha alterada com sucesso.']);
}
if ($action==='reset') out(['error'=>'A recuperação automática não está ativada. Peça ao responsável pelo site para redefinir a senha no api-config.php.'],400);
// Lotes (usados para reordenar produtos) não possuem uma coleção única no corpo.
if ($action==='batch') {
  if (!logged()) out(['error'=>'Faça login para alterar os dados.'],401);
  $data=readData($file); $permitidas=['settings','produtos','pedidos'];
  foreach(($input['ops']??[]) as $op){
    $c=$op['collection']??''; $i=$op['id']??'';
    if(!in_array($c,$permitidas,true)||!$i) out(['error'=>'Operação de lote inválida.'],400);
    $data[$c]=$data[$c]??[];
    if(($op['type']??'')==='delete') unset($data[$c][$i]);
    else $data[$c][$i]=array_merge(($op['merge']??false)?($data[$c][$i]??[]):[], $op['data']??[]);
  }
  writeData($file,$data); out(['ok'=>true]);
}
$col=$input['collection']??''; $id=$input['id']??'';
$permitidas=['settings','produtos','pedidos']; if(!in_array($col,$permitidas,true)) out(['error'=>'Coleção inválida.'],400);
if (in_array($action,['get','list'],true)) {
  if ($col==='pedidos' && !logged()) out(['error'=>'Não autorizado.'],401);
  $data=readData($file); $items=$data[$col]??[];
  if($action==='get') out(['exists'=>isset($items[$id]),'data'=>$items[$id]??null]);
  $docs=[]; foreach($items as $key=>$value) $docs[]=['id'=>(string)$key,'data'=>$value]; out(['docs'=>$docs]);
}
if ($action==='add' && $col==='pedidos') { /* cliente pode registrar pedido */ }
elseif (!logged()) out(['error'=>'Faça login para alterar os dados.'],401);
$data=readData($file); if(!isset($data[$col])) $data[$col]=[];
if($action==='set' || $action==='update' || $action==='add') {
  if($action==='add') $id=bin2hex(random_bytes(8));
  if(!$id) out(['error'=>'Identificador ausente.'],400);
  $value=$input['data']??[];
  if($action==='update' || !empty($input['merge'])) $value=array_merge($data[$col][$id]??[],$value);
  $data[$col][$id]=$value; writeData($file,$data); out(['id'=>$id,'ok'=>true]);
}
if($action==='delete') { unset($data[$col][$id]); writeData($file,$data); out(['ok'=>true]); }
out(['error'=>'Ação inválida.'],400);
