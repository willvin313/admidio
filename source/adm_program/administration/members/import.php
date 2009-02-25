<?php
/******************************************************************************
 * Import-Assistent fuer Benutzerdaten
 *
 * Copyright    : (c) 2004 - 2009 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

require('../../system/common.php');
require('../../system/login_valid.php');

// nur berechtigte User duerfen User importieren
if(!$g_current_user->editUsers())
{
    $g_message->show('norights');
}

//pruefen ob in den aktuellen Servereinstellungen ueberhaupt file_uploads auf ON gesetzt ist...
if (ini_get('file_uploads') != '1')
{
    $g_message->show('no_fileuploads');
}

$role_condition = '';
if($g_current_user->isWebmaster() == false)
{
	$role_condition = ' AND rol_assign_roles = 0 ';
}

// Html-Kopf ausgeben
$g_layout['title']  = 'Benutzer importieren';
require(THEME_SERVER_PATH. '/overall_header.php');

// Html des Modules ausgeben
echo '
<form id="form_import" action="'.$g_root_path.'/adm_program/administration/members/import_function.php" method="post" enctype="multipart/form-data">
<div class="formLayout" id="import_form">
    <div class="formHead">Benutzer aus Datei importieren</div>
    <div class="formBody">
        <ul class="formFieldList">
            <li>
                <dl>
                    <dt>Format:</dt>
                    <dd>CSV</dd>
                </dl>
            </li>
            <li>
                <dl>
                    <dt><label for="userfile">Datei ausw&auml;hlen:</label></dt>
                    <dd><input id="userfile" name="userfile" size="30" type="file" /></dd>
                </dl>
            </li>
            <li>
                <dl>
                    <dt><label for="rol_id">Rolle zuordnen:</label></dt>
                    <dd>';
                        // Combobox mit allen Rollen ausgeben
                        echo generateRoleSelectBox(0,'',$role_condition);

                        echo '&nbsp;
                        <a class="thickbox" href="'. $g_root_path. '/adm_program/system/msg_window.php?err_code=role_assign&amp;window=true&amp;KeepThis=true&amp;TB_iframe=true&amp;height=200&amp;width=580"><img 
			                onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?err_code=role_assign\',this)" onmouseout="ajax_hideTooltip()"
			                class="iconHelpLink" src="'. THEME_PATH. '/icons/help.png" alt="Hilfe" title="" /></a>
                    </dd>
                </dl>
            </li>
            <li>
                <label for="user_import_mode">Bereits existierende Benutzer</label>&nbsp;
                <select size="1" id="user_import_mode" name="user_import_mode">
                    <option value="1" selected="selected">nicht bearbeiten</option>
                    <option value="2">duplizieren</option>
                    <option value="3">ersetzen</option>
                    <option value="4">ergänzen</option>
                </select>
            </li>
        </ul>

        <hr />

        <div class="formSubmit">
            <button name="zurueck" type="button" value="zurueck" onclick="history.back()"><img src="'. THEME_PATH. '/icons/back.png" alt="Zurück" />&nbsp;Zurück</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <button id="weiter" type="submit" value="weiter" tabindex="2">Weiter&nbsp;<img src="'. THEME_PATH. '/icons/forward.png" alt="Weiter" /></button>
        </div>
    </div>
</div>
</form>

<script type="text/javascript"><!--
    document.getElementById("userfile").focus();
--></script>';
    
require(THEME_SERVER_PATH. '/overall_footer.php');

?>