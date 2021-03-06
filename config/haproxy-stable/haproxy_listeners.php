<?php
/* $Id: load_balancer_virtual_server.php,v 1.6.2.1 2006/01/02 23:46:24 sullrich Exp $ */
/*
	haproxy_baclkends.php
	part of pfSense (https://www.pfsense.org/)
	Copyright (C) 2009 Scott Ullrich <sullrich@pfsense.com>
	Copyright (C) 2008 Remco Hoef <remcoverhoef@pfsense.com>
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require_once("guiconfig.inc");

$d_haproxyconfdirty_path = $g['varrun_path'] . "/haproxy.conf.dirty";

require_once("haproxy.inc");

if (!is_array($config['installedpackages']['haproxy']['ha_backends']['item'])) {
	$config['installedpackages']['haproxy']['ha_backends']['item'] = array();
}
$a_backend = &$config['installedpackages']['haproxy']['ha_backends']['item'];

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		config_lock();
		$retval = haproxy_configure();
		config_unlock();
		$savemsg = get_std_save_message($retval);
		unlink_if_exists($d_haproxyconfdirty_path);
	}
}

if ($_GET['act'] == "del") {
	if (isset($a_backend[$_GET['id']])) {
		if (!$input_errors) {
			unset($a_backend[$_GET['id']]);
			write_config();
			touch($d_haproxyconfdirty_path);
		}
		header("Location: haproxy_listeners.php");
		exit;
	}
}

$pf_version=substr(trim(file_get_contents("/etc/version")),0,3);
if ($pf_version < 2.0)
	$one_two = true;
	
$pgtitle = "Services: HAProxy: Listener";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<form action="haproxy_listeners.php" method="post">
<?php if($one_two): ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php endif; ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_haproxyconfdirty_path)): ?><p>
<?php print_info_box_np("The virtual server configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <?php
        /* active tabs */
        $tab_array = array();
	$tab_array[] = array("Settings", false, "haproxy_global.php");
        $tab_array[] = array("Listener", true, "haproxy_listeners.php");		
	$tab_array[] = array("Server Pool", false, "haproxy_pools.php");
	display_top_tabs($tab_array);
  ?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="20%" class="listhdrr">Name</td>
                  <td width="30%" class="listhdrr">Description</td>
                  <td width="20%" class="listhdrr">Address</td>
                  <td width="10%" class="listhdrr">Type</td>
                  <td width="10%" class="listhdrr">Server&nbsp;pool</td>
                  <td width="5%" class="list"></td>
		</tr>
<?php
		$i = 0;
		foreach ($a_backend as $backend):
		$textss = $textse = "";
		if ($backend['status'] != 'active') {
			$textss = "<span class=\"gray\">";
			$textse = "</span>";
		}
?>
                <tr>
                  <td class="listlr" ondblclick="document.location='haproxy_listeners_edit.php?id=<?=$i;?>';">
					<?=$textss . $backend['name'] . $textse;?>
                  </td>
                  <td class="listlr" ondblclick="document.location='haproxy_listeners_edit.php?id=<?=$i;?>';">
					<?=$textss . $backend['desc'] . $textse;?>
                  </td>
                  <td class="listlr" ondblclick="document.location='haproxy_listeners_edit.php?id=<?=$i;?>';">
<?php
			echo $textss;
			if($backend['extaddr'] == "any") 
				echo "0.0.0.0";
			elseif($backend['extaddr']) 
				echo $backend['extaddr'];
			else 
				echo get_current_wan_address('wan');
			echo ":" . $backend['port'];
			echo $textse;
?>
                  </td>
                  <td class="listlr" ondblclick="document.location='haproxy_listeners_edit.php?id=<?=$i;?>';">
					<?=$textss . $backend['type'] . $textse;?>
                  </td>
                  <td class="listlr" ondblclick="document.location='haproxy_listeners_edit.php?id=<?=$i;?>';">
					<?=$textss . $backend['pool'] . $textse;?>
                  </td>
                  <td class="list" nowrap>
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="haproxy_listeners_edit.php?id=<?=$i;?>"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
                        <td valign="middle"><a href="haproxy_listeners.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this entry?')"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                        <td valign="middle"><a href="haproxy_listeners_edit.php?dup=<?=$i;?>"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="5"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="haproxy_listeners_edit.php"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
	   </div>
	</table>
            </form>
<?php include("fend.inc"); ?>
</body>
</html>
