<?php
require __DIR__.'/config.php';
$db=new PDO('sqlite:'.__DIR__.'/bot.sqlite');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,username TEXT,first_name TEXT,balance INTEGER DEFAULT 0,last_work INTEGER DEFAULT 0)");
function tg($m,$d=[]){$c=curl_init('https://api.telegram.org/bot'.BOT_TOKEN.'/'.$m);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$d]);$r=curl_exec($c);curl_close($c);return $r;}
function msg($id,$t){tg('sendMessage',['chat_id'=>$id,'text'=>$t,'parse_mode'=>'HTML']);}
function user($db,$id){$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$id]);return $s->fetch(PDO::FETCH_ASSOC);}
if($_SERVER['REQUEST_METHOD']!=='POST'){if(empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off')exit('Нужен HTTPS');$u='https://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];echo tg('setWebhook',['url'=>$u]);exit;}
$x=json_decode(file_get_contents('php://input'),true);if(empty($x['message']))exit;$m=$x['message'];$chat=$m['chat']['id'];$f=$m['from'];$id=$f['id'];$name=$f['first_name']??'Бандит';$text=trim($m['text']??'');$p=preg_split('/\s+/',$text);$cmd=strtolower(explode('@',$p[0]??'')[0]);
$s=$db->prepare('INSERT OR IGNORE INTO users(id,username,first_name) VALUES(?,?,?)');$s->execute([$id,$f['username']??'',$name]);
$s=$db->prepare('UPDATE users SET username=?,first_name=? WHERE id=?');$s->execute([$f['username']??'',$name,$id]);
if($cmd==='/start'){msg($chat,"🔫 <b>Работа Бандита</b>\n\n💼 /work — заработать\n💰 /balance — баланс\n🏆 /top — топ\nℹ️ /help — помощь");exit;}
if($cmd==='/help'){msg($chat,"📋 <b>Команды</b>\n/work\n/balance\n/top".($id===ADMIN_ID?"\n\n👑 Админ:\n/give ID сумма\n/take ID сумма\n/set ID сумма":""));exit;}
if($cmd==='/balance'||$cmd==='/bal'){$u=user($db,$id);msg($chat,"💰 Баланс: <b>".number_format($u['balance'],0,' ',' ')." $</b>");exit;}
if($cmd==='/work'){$u=user($db,$id);$left=60-(time()-(int)$u['last_work']);if($left>0){msg($chat,"⏳ Подожди <b>{$left} сек.</b>");exit;}$a=random_int(500,2500);$s=$db->prepare('UPDATE users SET balance=balance+?,last_work=? WHERE id=?');$s->execute([$a,time(),$id]);$u=user($db,$id);msg($chat,"💼 Работа выполнена!\n💵 +<b>$a $</b>\n💰 Баланс: <b>".number_format($u['balance'],0,' ',' ')." $</b>");exit;}
if($cmd==='/top'){$r=$db->query('SELECT first_name,balance FROM users ORDER BY balance DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);$o="🏆 <b>ТОП-10</b>\n\n";$i=1;foreach($r as $v)$o.=$i++.". ".htmlspecialchars($v['first_name'])." — <b>".number_format($v['balance'],0,' ',' ')." $</b>\n";msg($chat,$o);exit;}
if(in_array($cmd,['/give','/take','/set'])&&$id===ADMIN_ID){if(count($p)<3||!ctype_digit($p[1])||!is_numeric($p[2])){msg($chat,"❌ Формат: <code>$cmd ID сумма</code>");exit;}$target=(int)$p[1];$a=max(0,(int)$p[2]);if(!user($db,$target)){msg($chat,"❌ Игрок ещё не запускал бота.");exit;}if($cmd==='/give')$q='UPDATE users SET balance=balance+? WHERE id=?';elseif($cmd==='/take')$q='UPDATE users SET balance=MAX(0,balance-?) WHERE id=?';else$q='UPDATE users SET balance=? WHERE id=?';$s=$db->prepare($q);$s->execute([$a,$target]);$u=user($db,$target);msg($chat,"✅ Готово. Баланс: <b>".number_format($u['balance'],0,' ',' ')." $</b>");exit;}
msg($chat,"🤔 Неизвестная команда. /help");
?>