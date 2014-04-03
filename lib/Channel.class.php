<?php
namespace GearsIRCd\lib;

class Channel {
	public $users = array();
	
	private $q = array();
	private $a = array();
	private $o = array();
	private $h = array();
	private $v = array();
	
	private $topic = null;
	private $topicTime = null;
	private $topicUser = null;	
	
	private $inviteOnly = false;
	private $banned = array();
	private $plusModeration = false;
	
	private $channelName;
	private $channelOwner = null; // For ChanServ	
	
	public function __construct($channel) {
		$this->channelName = strtolower($channel);
	}
	
	public function AddUser($user) {
		if (count($this->users) == 0) {
			$this->OperatorMode($user, true);
		}
		
		if ($this->IsUserInChannel($user)) {
			return false;
		}
		$this->users[] = $user;
		return true;
	}
	
	public function RemoveUser($user) {
		$this->users = Utilities::RemoveFromArray($this->users, $user);
		$this->q = Utilities::RemoveFromArray($this->q, $user);
		$this->a = Utilities::RemoveFromArray($this->a, $user);
		$this->o = Utilities::RemoveFromArray($this->o, $user);
		$this->h = Utilities::RemoveFromArray($this->h, $user);
		$this->v = Utilities::RemoveFromArray($this->v, $user);
		return true;
	}
	
	public function Topic($new = "", $user = null) {
		if ($user != null) {
			if (strlen($new) > 300) {
				$new = substr($new, 0, 300);
			}
			$this->topic = $new;
			$this->topicTime = time();
			$this->topicUser = $user->Nick();
		}
		
		return array($this->topic, $this->topicTime, $this->topicUser);
	}
	
	public function OwnerMode($user, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->q)) {
				$this->q[] = $user;
			}
			return true;
		}
		
		return in_array($user, $this->q);
	}
	
	public function AdminMode($user = null, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->a)) {
				$this->a[] = $user;
			}
			return true;
		}
		
		return in_array($user, $this->a);
	}
	
	public function OperatorMode($user = null, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->o)) {
				$this->o[] = $user;
			}
			return true;
		}
		
		return in_array($user, $this->o);
	}
	
	public function HalfopMode($user = null, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->h)) {
				$this->h[] = $user;
			}
			return true;
		}
		
		return in_array($user, $this->h);
	}
	
	public function VoiceMode($user = null, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->v)) {
				$this->v[] = $user;
			}
			return true;
		}
		
		return in_array($user, $this->v);
	}
	
	public function ModerationMode($on = null) {
		if (is_bool($on) && $on != null) {
			$this->plusModeration = $on;
			return true;
		}
		return $this->plusModeration;
	}
	
	public function IsBanned($user) {
		foreach ($this->banned as $ban) {
			if (Utilities::MatchHostmask(Utilities::UserToFullHostmask($user), $ban)) {
				return true;
			}
		}
		return false;
	}
	
	public function IsVoiceOrAbove($user) {
		return ($this->VoiceMode($user) || $this->IsHalfOpOrAbove($user));
	}
	
	public function IsHalfOpOrAbove($user) {
		return ($this->HalfopMode($user) || $this->IsOpOrAbove($user));
	}
	
	public function IsOpOrAbove($user) {
		return ($this->OperatorMode($user) || $this->AdminMode($user) || $this->OwnerMode($user));
	}
	
	public function IsUserInChannel($user) {
		return in_array($user, $this->users);
	}
	
	public function Name() {
		return $this->channelName;
	}
}
?>