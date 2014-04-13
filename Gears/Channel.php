<?php
namespace GearsIRCd;

class Channel
{
	public $users = array();
	public $banned = array();
	
	private $q = array();
	private $a = array();
	private $o = array();
	private $h = array();
	private $v = array();
	
	private $topic = null;
	private $topicTime = null;
	private $topicUser = null;	
	
	private $inviteOnly = false;	
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
		$this->users = \GearsIRCd\Utilities::RemoveFromArray($this->users, $user);
		$this->q = \GearsIRCd\Utilities::RemoveFromArray($this->q, $user);
		$this->a = \GearsIRCd\Utilities::RemoveFromArray($this->a, $user);
		$this->o = \GearsIRCd\Utilities::RemoveFromArray($this->o, $user);
		$this->h = \GearsIRCd\Utilities::RemoveFromArray($this->h, $user);
		$this->v = \GearsIRCd\Utilities::RemoveFromArray($this->v, $user);
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
			else {
				$this->q = \GearsIRCd\Utilities::RemoveFromArray($this->q, $user);
			}
			return true;
		}
		
		return in_array($user, $this->q);
	}
	
	public function AdminMode($user, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->a)) {
				$this->a[] = $user;
			}
			else {
				$this->a = \GearsIRCd\Utilities::RemoveFromArray($this->a, $user);
			}
			return true;
		}
		
		return in_array($user, $this->a);
	}
	
	public function OperatorMode($user, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->o)) {
				$this->o[] = $user;
			}
			else {
				$this->o = \GearsIRCd\Utilities::RemoveFromArray($this->o, $user);
			}
			return true;
		}
		
		return in_array($user, $this->o);
	}
	
	public function HalfopMode($user, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->h)) {
				$this->h[] = $user;
			}
			else {
				$this->h = \GearsIRCd\Utilities::RemoveFromArray($this->h, $user);
			}
			return true;
		}
		
		return in_array($user, $this->h);
	}
	
	public function VoiceMode($user, $addUser = false) {
		if ($addUser === true) {
			if (!in_array($user, $this->v)) {
				$this->v[] = $user;
			}
			else {
				$this->v = \GearsIRCd\Utilities::RemoveFromArray($this->v, $user);
			}
			return true;
		}
		
		return in_array($user, $this->v);
	}
	
	public function ModerationMode($on = null) {
		if (is_bool($on)) {
			$this->plusModeration = $on;
			return true;
		}
		return $this->plusModeration;
	}
	
	public function BanMode($bool, $mask, $setby = null) {
		if (($bool === true) && (!empty($setby))) {
			if (!isset($this->banned[strtolower($mask)])) {
				$this->banned[strtolower($mask)] = array(
					"Mask" => $mask,
					"SetBy" => $setby,
					"Time" => time()
				);
				return true;
			}
			return false;
		}
		elseif ($bool === false) {
			if (isset($this->banned[strtolower($mask)])) {
				unset($this->banned[strtolower($mask)]);
				return true;
			}
			return false;
		}
	}
	
	public function IsBanned($user) {
		foreach ($this->banned as $banMask => $ban) {
			if (\GearsIRCd\Utilities::MatchHostmask(\GearsIRCd\Utilities::UserToFullHostmask($user), $banMask)) {
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