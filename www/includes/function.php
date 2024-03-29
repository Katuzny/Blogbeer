<?php
require_once 'vendor/autoload.php';

date_default_timezone_set('Europe/Paris') ;
/**
* retourne le nom du dossier
*
* @return string
*/
function uri($cible="")//:string
{
	global $racine; //Permet de récupérer une variable externe à la fonction
	$uri = "http://".$_SERVER['HTTP_HOST']; 
	$folder = "";
	if(!$racine) {
		$folder = basename(dirname(dirname(__FILE__))).'/'; //Dossier courant
	}
	return $uri.'/'.$folder.$cible;
}
/**
* crée une connexion à la base de données
*	@return \PDO
*/
function getDB(	$dbuser='chrisk', 
				$dbpassword='', 
				$dbhost='localhost',
				$dbname='bieres') //:\PDO
{
	
	$dsn = 'mysql:dbname='.$dbname.';host='.$dbhost.';charset=UTF8';
	try {
    	$pdo = new PDO($dsn, $dbuser, $dbpassword);
    	//definit mode de recupération en mode tableau associatif
    	// $user["lastname"];
    	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    	//definit mode de recupération en mode Objet
    	//$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    	// $user->lastname;
    	return $pdo;
	} catch (PDOException $e) {
    	echo 'Connexion échouée : ' . $e->getMessage();
    	die();
	}
}
/**
*	génère un champ de formulaire de type input
*	@return String
*/
function input($name, $label,$value="", $type='text', $require=true)//:string
{
	$input = "<div class=\"form-group\"><label for=\"".
	$name."\">".$label.
	"</label><input id=\"".
	$name."\" type=\"".$type.
	"\" name=\"".$name."\" value=\"".$value."\" ";
	$input .= ($require)? "required": "";
	$input .= "></div>";
	return $input;
}
/**
* Connect le client
* @return boolean|void
*/
function userConnect($mail, $password, $verify=false){//:boolean|void
	require 'config.php';
	$sql = "SELECT * FROM users WHERE `mail`= ?";
	$pdo = getDB($dbuser, $dbpassword, $dbhost,$dbname);
		$statement = $pdo->prepare($sql);
		$statement->execute([htmlspecialchars($mail)]);
		$user = $statement->fetch();
		if(	$user && 
			password_verify(
			htmlspecialchars($password), $user['password']
		)){
				if($verify){
					return true;
					//exit();
				}
				if (session_status() != PHP_SESSION_ACTIVE){
					session_start();
				}
				unset($user['password']);
				$_SESSION['auth'] = $user;
				//connecté
				header('location: profil.php');
				exit();
		}else{
			if($verify){
				return false;
				//exit();
			}
			if (session_status() != PHP_SESSION_ACTIVE){
					session_start();
				}
			$_SESSION['auth'] = false;
			header('location: ?p=login');
			//TODO : err pas connecté
		}
}
/**
* verifie que l'utilisateur est connecté
* @return array|void
*/
function userOnly($verify=false){//:array|void|boolean
	if (session_status() != PHP_SESSION_ACTIVE){
		session_start();
	}
	// est pas defini et false
	if(!isset($_SESSION["auth"])){
		if($verify){
			return false;
		//exit();
		}
		header('location: login.php');
		exit();
	}
	return $_SESSION["auth"];
}

/**
* envoie un email
* @return string
*/
function envoiMail($objet, $mailto, $msg, $cci = true)//:string
{
	require 'config.php';
	if(!is_array($mailto)){
		$mailto = [ $mailto ];
	}
	// Create the Transport
	$transport = (new Swift_SmtpTransport('smtp.gmail.com', 587, 'tls'))
	->setUsername($defaultmail)
	->setPassword($mailpwd);
	// Create the Mailer using your created Transport
	$mailer = new Swift_Mailer($transport);
	// Create a message
	$message = (new Swift_Message($objet))
		->setFrom([$defaultmail]);
	if ($cci){
		$message->setBcc($mailto);
	}else{
		$message->setto($mailto);
	}
	if(is_array($msg) && array_key_exists("html", $msg) && array_key_exists("text", $msg))
	{
		$message->setBody($msg["html"], 'text/html');
		// Add alternative parts with addPart()
		$message->addPart($msg["text"], 'text/plain');
	}else if(is_array($msg) && array_key_exists("html", $msg) ){
		$message->setBody($msg["html"], 'text/html');
		$message->addPart($msg["html"], 'text/plain');
	}else if(is_array($msg) && array_key_exists("text", $msg)){
		$message->setBody($msg["text"], 'text/plain');
	}else if(is_array($msg)){
		die('erreur une clé n\'est pas bonne'); 
	}else{
		$message->setBody($msg, 'text/plain');
	}
	
	// Send the message
	return $mailer->send($message);
}
function rand_pwd($nb_car = 10, $chaine ='azertyuiopqsdfghjklmwxcvbn0123456789') {
	$nb_lettre = strlen($chaine) -1;
	$generation = '';
	for($i=0; $i < $nb_car; $i++) {
		$pos = mt_rand(0, $nb_lettre);
		$car = $chaine[$pos];
		$generation .= $car;
	}
	return $generation;
}
function setFlashMessages($type, $message) {
	// Start a Session
	if (!session_id()) @session_start();
		
	// Instantiate the class
	$msg = new \Plasticbrain\FlashMessages\FlashMessages();
	// Add messages
	$msg->$type($message);
	// If you need to check for errors (eg: when validating a form) you can:
	if ($msg->hasErrors()) {
		// There ARE errors
	} else {
	  // There are NOT any errors
	}
	// Wherever you want to display the messages simply call:
	$msg->display();
}
