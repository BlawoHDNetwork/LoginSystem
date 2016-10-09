<?php

namespace BlawoHD;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class LoginSystem extends PluginBase implements Listener {

	public $Auth = array();
	public $prefix = TextFormat::GRAY."[".TextFormat::GOLD."LoginSystem".TextFormat::GRAY."]".TextFormat::WHITE." ";

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info($this->prefix.TextFormat::GREEN."Plugin wurde Geladen!");
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder()."Players");
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();

		if($this->isAuth($name)){
			unset($this->Auth[array_search(strtolower($name), $this->Auth)]);
		}

		if($this->isRegistered($name)){
			$player->sendMessage($this->prefix."Logge dich ein mit /login <passwort>");
		} else {
			$player->sendMessage($this->prefix."Registriere dich mit /register <passwort>");
		}
	}
	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();

		@mkdir($this->getDataFolder()."Players/".strtolower($name{0}));
	}
	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();

		if(!$this->isAuth($name)){
			$event->setCancelled();
			$player->sendTip($this->prefix.TextFormat::RED."Bitte logge dich ein!");
		}
	}
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();

		if(!$this->isAuth($name)){
			$event->setCancelled();
			$player->sendMessage($this->prefix.TextFormat::RED."Bitte logge dich ein!");
		}
	}
	public function onCmdProcess(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$msg = $event->getMessage();

		$args = explode(" ", $msg);

		$command = array_shift($args);

		if(!$this->isAuth($name)){
			if($msg{0} == "/"){
				if(strtolower($command) != "/register" && strtolower($command) != "/login"){
					$event->setCancelled();
					$player->sendMessage($this->prefix.TextFormat::RED."Bitte logge dich ein!");
				}
			}
		}
	}

	public function isAuth($name){
		if(in_array(strtolower($name), $this->Auth)){
			return true;
		}

		return false;
	}

	public function isRegistered($name){
		if(file_exists($this->getDataFolder()."Players/".strtolower($name{0})."/".strtolower($name).".yml")){
			return true;
		}

		return false;
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		$name = $sender->getName();
		if($cmd->getName() == "register"){
			if(!$this->isRegistered($name)){
				if(!empty($args[0])){
					$playerfile = new Config($this->getDataFolder()."Players/".strtolower($name{0})."/".strtolower($name).".yml", Config::YAML);
					$playerfile->set("UUID", $sender->getClientID());
					$playerfile->set("IP", $sender->getAddress());
					$playerfile->set("Passwort", md5($args[0]));
					$playerfile->save();
					$sender->sendMessage($this->prefix.TextFormat::GREEN."Du hast dich Erfolgreich registriert!");
					$this->Auth[] = strtolower($name);
				} else {
					$sender->sendMessage($this->prefix.TextFormat::RED."/register <passwort>");
				}
			} else {
				$sender->sendMessage($this->prefix.TextFormat::RED."Du bist bereits registriert!");
			}
		}
		if($cmd->getName() == "login"){
			if($this->isRegistered($name)){
				if(!$this->isAuth($name)){
					if(!empty($args[0])){
						$playerfile = new Config($this->getDataFolder()."Players/".strtolower($name{0})."/".strtolower($name).".yml", Config::YAML);
						$pw = $playerfile->get("Passwort");
						if($pw === md5($args[0])){
							$this->Auth[] = strtolower($name);
							$sender->sendMessage($this->prefix.TextFormat::GREEN."Du hast dich Erfolgreich eingeloggt!");
						} else {
							$sender->sendMessage($this->prefix.TextFormat::RED."Falsches Passwort!");
						}
					} else {
						$sender->sendMessage($this->prefix.TextFormat::RED."/login <passwort>");
					}
				} else {
					$sender->sendMessage($this->prefix.TextFormat::RED."Du bist bereits eingeloggt");
				}
			} else {
				$sender->sendMessage($this->prefix.TextFormat::RED."Du bist noch nicht registriert!");
			}
		}

	}
}
