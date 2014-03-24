<?php
/**
* Narod mon 
*
* App_narodmon
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 18:03:25 [Mar 24, 2014])
*/
//
//
class app_narodmon extends module {
/**
* app_narodmon
*
* Module class constructor
*
* @access private
*/
function app_narodmon() {
  $this->name="app_narodmon";
  $this->title="NarodMon.ru";
  $this->module_category="CMS";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}

/**
* Title
*
* Description
*
* @access public
*/
 function sendData() {
  $this->getConfig();  


  $send="#".$this->config['SERVER_MAC'];
  if ($this->config['SERVER_NAME']) {
   $send.="#".$this->config['SERVER_NAME'];
  }
  $send.="\n";

  if ($this->config['TEMP_ID'] && $this->config['TEMP_LINKED']) {
   $temp_value=getGlobal($this->config['TEMP_LINKED']);
   if ($temp_value!='') {
    if ($temp_value>0 && !preg_match('/^\+/', $temp_value)) {
     $temp_value='+'.$temp_value;
    }
    $send.='#'.$this->config['TEMP_ID'].'#'.$temp_value;
    $send.="\n";
   }
  }
  if ($this->config['PRESS_ID'] && $this->config['PRESS_LINKED']) {
   $press_value=getGlobal($this->config['PRESS_LINKED']);
   if ($press_value!='') {
    $send.='#'.$this->config['PRESS_ID'].'#'.$press_value;
    $send.="\n";
   }
  }

  $send.="##";

  $fp = @fsockopen("tcp://narodmon.ru", 8283, $errno, $errstr);
  if(!$fp) exit("ERROR(".$errno."): ".$errstr);
  fwrite($fp, $send);

  $result='';
  while (!feof($fp)) {
    $result.=fread($fp, 128);
  }

  fclose($fp);

  $this->config['LATEST_SENT']=$send;
  $this->config['LATEST_RESULT']=$result;
  $this->config['LATEST_UPDATE']=time();
  $this->saveConfig();  
 }


/**
* Title
*
* Description
*
* @access public
*/
 function checkData() {
  $this->getConfig();
  $every=$this->config['EVERY'];
  if ((time()-$this->config['LATEST_UPDATE'])>$every*60) {
   echo "Sending data to narodmon.ru\n";
   $this->sendData();
  }
 }

/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();


 $fields=array('server_mac', 'server_name', 'temp_id', 'temp_linked', 'press_id', 'press_linked', 'every');

 if ($this->mode=='refresh') {
  $this->sendData();
  $this->redirect("?");
 }

 if ($this->mode=='update') {

  $ok=1;

  foreach($fields as $k) {
   global ${$k};
   $this->config[strtoupper($k)]=trim(${$k});
  }

  if (!preg_match('/^[0-9A-Za-z:\-]+$/', $this->config['SERVER_MAC'])) {
   $ok=0;
   $out['ERR_SERVER_MAC']=1;
  }

  if (!preg_match('/^[0-9A-Za-z:\-]+$/', $this->config['TEMP_ID'])) {
   $ok=0;
   $out['ERR_TEMP_ID']=1;
  }

  if ($this->config['PRESS_ID'] && !preg_match('/^[0-9A-Za-z:\-]+$/', $this->config['PRESS_ID'])) {
   $ok=0;
   $out['ERR_PRESS_ID']=1;
  }

  if (getGlobal($this->config['TEMP_LINKED'])=='') {
   $ok=0;
   $out['ERR_TEMP_LINKED']=1;
  }

  if ($this->config['PRESS_ID'] && getGlobal($this->config['PRESS_LINKED'])=='') {
   $ok=0;
   $out['ERR_PRESS_LINKED']=1;
  }



  if ($ok) {
   $this->saveConfig();
   $this->redirect("?mode=refresh");
  }
 }

 foreach($fields as $k) {
  $out[strtoupper($k)]=$this->config[strtoupper($k)];
 }
 $out['LATEST_RESULT']=$this->config['LATEST_RESULT'];
 $out['LATEST_SENT']=$this->config['LATEST_SENT'];
 $out['LATEST_UPDATE']=date('Y-m-d H:i:s', $this->config['LATEST_UPDATE']);


}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDI0LCAyMDE0IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>