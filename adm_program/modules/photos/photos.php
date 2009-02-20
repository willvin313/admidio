<?php
/******************************************************************************
 * Photogalerien
 *
 * Copyright    : (c) 2004 - 2008 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Jochen Erkens
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Uebergaben:
 *
 * pho_id:      id des Albums dessen Fotos angezeigt werden sollen
 * thumb_seite: welch Seite der Thumbnails ist die aktuelle
 * start:       mit welchem Element beginnt die Albumliste
 * locked:      das Album soll freigegebn/gesperrt werden
 *
 *****************************************************************************/

require_once('../../system/classes/table_photos.php');
require_once('../../system/common.php');
require_once('../../system/classes/image.php');
require_once('../../system/classes/htaccess.php');
$g_debug  = 1;
// pruefen ob das Modul ueberhaupt aktiviert ist
if ($g_preferences['enable_photo_module'] == 0)
{
    // das Modul ist deaktiviert
    $g_message->show('module_disabled');
}
elseif($g_preferences['enable_photo_module'] == 2)
{
    // nur eingeloggte Benutzer duerfen auf das Modul zugreifen
    require_once('../../system/login_valid.php');
}

//ID Pruefen
if(isset($_GET['pho_id']) && is_numeric($_GET['pho_id']))
{
    $pho_id = $_GET['pho_id'];
}
else 
{
    $pho_id = NULL;
}

unset($_SESSION['photo_album_request']);

//Wurde keine Album uebergeben kann das Navigationsstack zurueckgesetzt werden
if ($pho_id == NULL)
{
    $_SESSION['navigation']->clear();
}

//URL auf Navigationstack ablegen
$_SESSION['navigation']->addUrl(CURRENT_URL);

//Modulüberschrift
$req_headline = 'Fotogalerien';
if(isset($_GET['headline']))
{
    $_SESSION['photomodul_headline'] = strStripTags($_GET['headline']);
    $req_headline = $_SESSION['photomodul_headline'];
}
else if(isset($_SESSION['photomodul_headline']))
{
	$req_headline = $_SESSION['photomodul_headline'];
}

//aktuelles Bild
if(array_key_exists('start', $_GET))
{
    if(is_numeric($_GET['start']) == false)
    {
        $g_message->show('invalid');
    }
    $album_element = $_GET['start'];
}
else
{
    $album_element = 0;
}

//aktuelle Albumseite
if(array_key_exists('thumb_seite', $_GET))
{
    if(is_numeric($_GET['thumb_seite']) == false)
    {
        $g_message->show('invalid');
    }
    $thumb_seite = $_GET['thumb_seite'];
}
else
{
    $thumb_seite = 1;
}

//ggf. Ordner für Fotos anlegen
if(!file_exists(SERVER_PATH. '/adm_my_files/photos'))
{
    mkdir(SERVER_PATH. '/adm_my_files/photos', 0777);
    chmod(SERVER_PATH. '/adm_my_files/photos', 0777);
}
$protection = new Htaccess(SERVER_PATH. '/adm_my_files');
$protection->protectFolder();

// Fotoalbums-Objekt erzeugen oder aus Session lesen
if(isset($_SESSION['photo_album']) && $_SESSION['photo_album']->getValue('pho_id') == $pho_id)
{
    $photo_album =& $_SESSION['photo_album'];
    $photo_album->db =& $g_db;
}
else
{
    // einlesen des Albums falls noch nicht in Session gespeichert
    $photo_album = new TablePhotos($g_db);
    if($pho_id > 0)
    {
        $photo_album->readData($pho_id);
    }

    $_SESSION['photo_album'] =& $photo_album;
}

// pruefen, ob Album zur aktuellen Organisation gehoert
if($pho_id > 0 && $photo_album->getValue('pho_org_shortname') != $g_organization)
{
    $g_message->show('invalid');
}   

/*********************LOCKED************************************/
if(isset($_GET['locked']))
{
    $locked = $_GET['locked'];
}
else
{
    $locked = NULL;
}

if(!is_numeric($locked) && $locked!=NULL)
{
    $g_message->show('invalid');
}

//Falls gefordert und Foto-edit-rechte, aendern der Freigabe
if($locked=='1' || $locked=='0')
{
    // erst pruefen, ob der User Fotoberarbeitungsrechte hat
    if(!$g_current_user->editPhotoRight())
    {
        $g_message->show('photoverwaltunsrecht');
    }
    
    $photo_album->setValue('pho_locked', $locked);
    $photo_album->save();

    //Zurueck zum Elternalbum    
    $pho_id = $photo_album->getValue('pho_pho_id_parent');
    $photo_album->readData($pho_id);
}

/*********************HTML_TEIL*******************************/

$g_layout['title']  = $req_headline;
$g_layout['header'] = '';

if($g_preferences['enable_rss'] == 1)
{
    $g_layout['header'] =  $g_layout['header']. '
        <link type="application/rss+xml" rel="alternate" title="'. $g_current_organization->getValue('org_longname'). ' - Fotos"
            href="'.$g_root_path.'/adm_program/modules/photos/rss_photos.php" />';
};

if($g_current_user->editPhotoRight())
{
    $g_layout['header'] = $g_layout['header']. '
        <script type="text/javascript" src="'.$g_root_path.'/adm_program/system/js/ajax.js"></script>
        <script type="text/javascript" src="'.$g_root_path.'/adm_program/system/js/delete.js"></script>';
}

//Photomodulspezifische CSS laden
$g_layout['header'] = $g_layout['header']. '
		<link rel="stylesheet" href="'. THEME_PATH. '/css/photos.css" type="text/css" media="screen" />';

// Html-Kopf ausgeben
require(THEME_SERVER_PATH. '/overall_header.php');


//Ueberschift
echo '<h1 class="moduleHeadline">'.$g_layout['title'].'</h1>';

//Breadcrump bauen
$navilink = '';
$pho_parent_id = $photo_album->getValue('pho_pho_id_parent');
$photo_album_parent = new TablePhotos($g_db);

while ($pho_parent_id > 0)
{
    // Einlesen des Eltern Albums
    $photo_album_parent->readData($pho_parent_id);
    
    //Link zusammensetzen
    $navilink = '&nbsp;&gt;&nbsp;<a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$photo_album_parent->getValue('pho_id').'">'.
        $photo_album_parent->getValue('pho_name').'</a>'.$navilink;

    //Elternveranst
    $pho_parent_id = $photo_album_parent->getValue('pho_pho_id_parent');
}

if($pho_id > 0)
{
    //Ausgabe des Linkpfads
    echo '<div class="navigationPath">
            <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php"><img src="'. THEME_PATH. '/icons/application_view_tile.png" alt="Fotogalerien" /></a>
            <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php">Fotogalerien</a>'.$navilink.'
            &nbsp;&gt;&nbsp;'.$photo_album->getValue('pho_name').'         
        </div>';
}

//bei Seitenaufruf mit Moderationsrechten
if($g_current_user->editPhotoRight())
{
    //Album anlegen
    echo '
    <ul class="iconTextLinkList">
        <li>
            <span class="iconTextLink">
                <a href="'.$g_root_path.'/adm_program/modules/photos/photo_album_new.php?job=new&amp;pho_id='.$pho_id.'">
    	           <img src="'. THEME_PATH. '/icons/add.png" alt="Album anlegen" title="Album anlegen" /></a>
                <a href="'.$g_root_path.'/adm_program/modules/photos/photo_album_new.php?job=new&amp;pho_id='.$pho_id.'">Album anlegen</a>
            <span>
    </li>';        
    if($pho_id > 0)
    {
        echo '
        <li>
            <span class="iconTextLink">
                <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photoupload.php?pho_id='.$pho_id.'">
                	<img src="'. THEME_PATH. '/icons/photo_upload.png" alt="Fotos hochladen" title="Fotos hochladen"/></a>
                <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photoupload.php?pho_id='.$pho_id.'">Fotos hochladen</a>
            </span>
        </li>';
    }
    echo '</ul>';
}


//Anlegen der Tabelle
echo '<div class="photoModuleContainer">';
    /*************************THUMBNAILS**********************************/
    //Nur wenn uebergebenes Album Bilder enthaelt
    if($photo_album->getValue('pho_quantity') > 0)
    {        
        //Aanzahl der Bilder
        $bilder = $photo_album->getValue('pho_quantity');
        
        //Ordnerpfad
        $ordner_foto = '/adm_my_files/photos/'.$photo_album->getValue('pho_begin').'_'.$photo_album->getValue('pho_id');
        $ordner      = SERVER_PATH. $ordner_foto;
        $ordner_url  = $g_root_path. $ordner_foto;

        //Nachsehen ob Thumnailordner existiert und wenn nicht SafeMode ggf. anlegen
        if(!file_exists($ordner.'/thumbnails'))
        {
            mkdir($ordner.'/thumbnails', 0777);
            chmod($ordner.'/thumbnails', 0777);
        }
        
        //Differenz
        $difference = $g_preferences['photo_thumbs_row']-$g_preferences['photo_thumbs_column'];

		//Thumbnails pro Seite
        $thumbs_per_side = $g_preferences['photo_thumbs_row']*$g_preferences['photo_thumbs_column'];
        	
        //Popupfenstergröße
        $popup_height = $g_preferences['photo_show_height']+210;
        $popup_width  = $g_preferences['photo_show_width']+70;
        
        //Thickboxgröße
        $thickbox_height = $g_preferences['photo_show_height']+90;
        $thickbox_width  = $g_preferences['photo_show_width'];

        //Album Seitennavigation
		function photoAlbumPageNavigation()
		{
			global $thumb_seiten;
			global $thumb_seite;
			global $photo_album;
			global $thumbs_per_side;
			global $g_root_path;
			       	
			//Ausrechnen der Seitenzahl
        	if (settype($photo_album->getValue('pho_quantity'), 'int') || settype($thumb_seiten, 'int'))
        	{
        	    $thumb_seiten = round($photo_album->getValue('pho_quantity') / $thumbs_per_side);
        	}
			
        	if ($thumb_seiten * $thumbs_per_side < $photo_album->getValue('pho_quantity'))
        	{
        	    $thumb_seiten++;
        	}
			if($thumb_seiten > 1)
			{
				//Container mit Navigation
        		echo ' <div class="pageNavigation" id="photoPageNavigation">';
        		    //Seitennavigation
        		    echo 'Seite:&nbsp;';
    			
        		    //Vorherige thumb_seite
        		    $vorseite=$thumb_seite-1;
        		    if($vorseite>=1)
        		    {
        		        echo '
        		        <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?thumb_seite='.$vorseite.'&amp;pho_id='.$photo_album->getValue('pho_id').'">
        		            <img src="'. THEME_PATH. '/icons/back.png" alt="Vorherige" />
        		        </a>
        		        <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?thumb_seite='.$vorseite.'&amp;pho_id='.$photo_album->getValue('pho_id').'">Vorherige</a>&nbsp;';
        		    }
    			
        		    //Seitenzahlen
        		    for($s=1; $s<=$thumb_seiten; $s++)
        		    {
        		        if($s==$thumb_seite)
        		        {
        		            echo $thumb_seite.'&nbsp;';
        		        }
        		        if($s!=$thumb_seite){
        		            echo'<a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?thumb_seite='.$s.'&pho_id='.$photo_album->getValue('pho_id').'">'.$s.'</a>&nbsp;';
        		        }
        		    }
    			
        		    //naechste thumb_seite
        		    $nachseite=$thumb_seite+1;
        		    if($nachseite<=$thumb_seiten){
        		        echo '
        		        <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?thumb_seite='.$nachseite.'&amp;pho_id='.$photo_album->getValue('pho_id').'">Nächste</a>
        		        <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?thumb_seite='.$nachseite.'&amp;pho_id='.$photo_album->getValue('pho_id').'">
        		            <img src="'. THEME_PATH. '/icons/forward.png" alt="N&auml;chste" />
        		        </a>';
        		    }
        		echo '</div>';
        	}
		}
                  
        //Thumbnailtabelle
        echo '<ul id="photoThumbnailTable">';
            for($zeile=1;$zeile<=$g_preferences['photo_thumbs_row'];$zeile++)//durchlaufen der Tabellenzeilen
            {
                echo '<li class="photoThumbnailRow"><ul>';
                for($spalte=1;$spalte<=$g_preferences['photo_thumbs_column'];$spalte++)//durchlaufen der Tabellenzeilen
                {
                     echo '<li class="photoThumbnailColumn">';
                     echo '<ul class="photoThumbnailElement">';
                    //Errechnug welches Bild ausgegeben wird
                    $bild = ($thumb_seite*$thumbs_per_side)-$thumbs_per_side+($zeile*$g_preferences['photo_thumbs_column'])-$g_preferences['photo_thumbs_row']+$spalte+$difference;
                    if ($bild <= $bilder)
                    {
                        //Popup-Mode
                        if($g_preferences['photo_show_mode']==0)
                        {
                            echo '<div>
                                <img onclick="window.open("'.$g_root_path.'/adm_program/modules/photos/photo_presenter.php?bild='.$bild.'&amp;pho_id='.$pho_id.'","msg", "height='.$popup_height.', width='.$popup_width.',left=162,top=5")" 
                                    src="photo_show.php?pho_id='.$pho_id.'&pic_nr='.$bild.'&pho_begin='.$photo_album->getValue('pho_begin').'&thumb=true" alt="'.$bild.'" />
                            </div>';
                        }

                        //Thickbox-Mode
                        elseif($g_preferences['photo_show_mode']==1)
                        {
                            echo 
                            '<li>
                                <a class="thickbox" href="'.$g_root_path.'/adm_program/modules/photos/photo_presenter.php?bild='.$bild.'&amp;pho_id='.$pho_id.'&amp;KeepThis=true&amp;TB_iframe=true&amp;height='.$thickbox_height.'&amp;width='.$thickbox_width.'">
                                	<img class="photoThumbnail" src="photo_show.php?pho_id='.$pho_id.'&amp;pic_nr='.$bild.'&amp;pho_begin='.$photo_album->getValue('pho_begin').'&amp;thumb=true" alt="'.$bild.'" />
                                </a>
                            </li>';
                        }

                        //Gleichesfenster-Mode
                        elseif($g_preferences['photo_show_mode']==2)
                        {
                            echo '<div>
                                <img onclick="self.location.href="'.$g_root_path.'/adm_program/modules/photos/photo_presenter.php?bild='.$bild.'&amp;pho_id='.$pho_id.'"" 
                                    src="photo_show.php?pho_id='.$pho_id.'&amp;pic_nr='.$bild.'&amp;pho_begin='.$photo_album->getValue('pho_begin').'&amp;thumb=true" />
                            </div>';
                        }   
                        
                        //Buttons fuer moderatoren
                        if($g_current_user->editPhotoRight())
                        {
                            echo '
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photo_function.php?pho_id='.$pho_id.'&amp;bild='.$bild.'&amp;job=rotate&amp;direction=left"><img 
                                src="'. THEME_PATH. '/icons/arrow_turn_left.png" alt="Gegen den Uhrzeigersinn drehen" title="Gegen den Uhrzeigersinn drehen" /></a>
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photo_function.php?pho_id='.$pho_id.'&amp;bild='.$bild.'&amp;job=rotate&amp;direction=right"><img 
                                src="'. THEME_PATH. '/icons/arrow_turn_right.png" alt="Mit dem Uhrzeigersinn drehen" title="Mit dem Uhrzeigersinn drehen" /></a>
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photo_function.php?pho_id='.$pho_id.'&amp;bild='.$bild.'&amp;job=delete_request"><img 
                                src="'. THEME_PATH. '/icons/delete.png" alt="Foto löschen" title="Foto löschen" /></a>';
                        }
                        if($g_valid_login == true && $g_preferences['enable_ecard_module'] == 1)
                        {
                            echo '
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/ecards/ecard_form.php?photo='.$bild.'&amp;pho_id='.$pho_id.'"><img 
                                src="'. THEME_PATH. '/icons/ecard.png" alt="Foto als Grußkarte versenden" title="Foto als Grußkarte versenden" /></a>';
                        }
                    }//if
                    //schleifen abbrechen
                    if ($bild == $bilder)
                    {
                        $zeile=$g_preferences['photo_thumbs_row'];
                        $spalte=$g_preferences['photo_thumbs_column'];
                    }
                    echo '</ul></li>';
                }//for
                echo '</ul></li>'; //Zeilenende
            }//for
        echo '</ul>';

		//Seitennavigation
        photoAlbumPageNavigation($photo_album);

        //Datum des Albums
        echo '<div class="editInformation" id="photoAlbumInformation">
            Datum: '.mysqldate('d.m.y', $photo_album->getValue('pho_begin'));
            if($photo_album->getValue('pho_end') != $photo_album->getValue('pho_begin'))
            {
                echo ' bis '.mysqldate('d.m.y', $photo_album->getValue('pho_end'));
            }
        echo '
        	<br />Fotos von: '.$photo_album->getValue('pho_photographers').'
        </div>';

        // Anleger und Veraendererinfos
        echo '
        <div class="editInformation">';
            $user_create = new User($g_db, $photo_album->getValue('pho_usr_id_create'));
            echo 'Angelegt von '. $user_create->getValue('Vorname'). ' '. $user_create->getValue('Nachname')
            .' am '. mysqldatetime('d.m.y h:i', $photo_album->getValue('pho_timestamp_create'));
            
            if($photo_album->getValue('pho_usr_id_change') > 0)
            {
                $user_change = new User($g_db, $photo_album->getValue('pho_usr_id_change'));
                echo '<br />Zuletzt bearbeitet von '. $user_change->getValue('Vorname'). ' '. $user_change->getValue('Nachname')
                .' am '. mysqldatetime('d.m.y h:i', $photo_album->getValue('pho_timestamp_change'));
            }
        echo '</div>';
    }
    /************************Albumliste*************************************/

    //erfassen der Alben die in der Albentabelle ausgegeben werden sollen
    $sql='      SELECT *
                FROM '. TBL_PHOTOS. '
                WHERE pho_org_shortname = "'.$g_organization.'"';
    if($pho_id==NULL)
    {
        $sql=$sql.' AND (pho_pho_id_parent IS NULL) ';
    }
    if($pho_id > 0)
    {
        $sql=$sql.' AND pho_pho_id_parent = '.$pho_id.'';
    }
    if (!$g_current_user->editPhotoRight())
    {
        $sql=$sql.' AND pho_locked = 0 ';
    }

    $sql = $sql.' ORDER BY pho_begin DESC ';
    $result_list = $g_db->query($sql);

    //Gesamtzahl der auszugebenden Alben
    $albums = $g_db->num_rows($result_list);

    // falls zum aktuellen Album Fotos und Unteralben existieren,
    // dann einen Trennstrich zeichnen
    if($photo_album->getValue('pho_quantity') > 0 && $albums > 0)
    {
        echo '<hr />';
    }

    $ignored=0; //Summe aller zu ignorierender Elemente
    $ignore=0; //Summe der zu ignorierenden Elemente auf dieser Seite
    for($x=0; $x<$albums; $x++)
    {
        $adm_photo_list = $g_db->fetch_array($result_list);
        //Hauptordner
        $ordner = SERVER_PATH. '/adm_my_files/photos/'.$adm_photo_list['pho_begin'].'_'.$adm_photo_list['pho_id'];
        
        if((!file_exists($ordner) || $adm_photo_list['pho_locked']==1) && (!$g_current_user->editPhotoRight()))
        {
            $ignored++;
            if($x >= $album_element+$ignored-$ignore)
                $ignore++;
        }
    }

    //Dateizeiger auf erstes auszugebendes Element setzen
    if($albums > 0 && $albums != $ignored)
    {
        $g_db->data_seek($result_list, $album_element+$ignored-$ignore);
    }
       
    $counter = 0;
    $sub_photo_album = new TablePhotos($g_db);

    for($x=$album_element+$ignored-$ignore; $x<=$album_element+$ignored+9 && $x<$albums; $x++)
    {
        $adm_photo_list = $g_db->fetch_array($result_list);
        // Daten in ein Photo-Objekt uebertragen
        $sub_photo_album->clear();
        $sub_photo_album->setArray($adm_photo_list);

        //Hauptordner
        $ordner = SERVER_PATH. '/adm_my_files/photos/'.$sub_photo_album->getValue('pho_begin').'_'.$sub_photo_album->getValue('pho_id');

        //wenn ja Zeile ausgeben
        if(file_exists($ordner) && ($sub_photo_album->getValue('pho_locked')==0) || $g_current_user->editPhotoRight())
        {
            if($counter == 0)
            {
                echo '<ul class="photoAlbumList">';
            }

            // Zufallsbild fuer die Vorschau ermitteln
            $shuffle_image = $sub_photo_album->shuffleImage();

            if($shuffle_image['shuffle_pho_id'] > 0)
            {
                //Pfad des Beispielbildes
                $bsp_pic_path = SERVER_PATH. '/adm_my_files/photos/'.$shuffle_image['shuffle_img_begin'].'_'.$shuffle_image['shuffle_pho_id'].'/'.$shuffle_image['shuffle_img_nr'].'.jpg';
            }
            else
            {
                //Wenn kein Bild gefunden wurde
                $bsp_pic_path = THEME_PATH. '/images/nopix.jpg';
            }

            //Ausgabe
            echo '
            <li id="pho_'.$sub_photo_album->getValue('pho_id').'">
            <dl>
                <dt>';
                    if(file_exists($ordner))
                    {
                        echo '
                        <a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$sub_photo_album->getValue('pho_id').'">
                            <img src="'.$g_root_path.'/adm_program/modules/photos/photo_show.php?pho_id='.$shuffle_image['shuffle_pho_id'].'&amp;pic_nr='.$shuffle_image['shuffle_img_nr'].'&amp;pho_begin='.$shuffle_image['shuffle_img_begin'].'&amp;scal='.$g_preferences['photo_preview_scale'].'&amp;side=y"
                            	class="imageFrame" alt="Zufallsfoto" />
                        </a>';
                    }
                echo '</dt>
                <dd>
    				<ul>
                        <li>';
                        if((!file_exists($ordner) && $g_current_user->editPhotoRight()) || ($sub_photo_album->getValue('pho_locked')==1 && file_exists($ordner)))
                        {
                            //Warnung fuer Leute mit Fotorechten: Ordner existiert nicht
                            if(!file_exists($ordner) && $g_current_user->editPhotoRight())
                            {
                                echo '<a class="thickbox" href="'. $g_root_path. '/adm_program/system/msg_window.php?err_code=folder_not_found&amp;window=true&amp;KeepThis=true&amp;TB_iframe=true&amp;height=250&amp;width=580"><img 
					                onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?err_code=folder_not_found\',this)" onmouseout="ajax_hideTooltip()"
					                class="iconHelpLink" src="'. THEME_PATH. '/icons/warning.png" alt="Warnung" title="" /></a>';
                            }
                            
                            //Hinweis fur Leute mit Photorechten: Album ist gesperrt
                            if($adm_photo_list["pho_locked"]==1 && file_exists($ordner))
                            {
                                echo '<a class="thickbox" href="'. $g_root_path. '/adm_program/system/msg_window.php?err_code=not_approved&amp;window=true&amp;KeepThis=true&amp;TB_iframe=true&amp;height=250&amp;width=580"><img 
					                onmouseover="ajax_showTooltip(event,\''.$g_root_path.'/adm_program/system/msg_window.php?err_code=not_approved\',this)" onmouseout="ajax_hideTooltip()"
					                class="iconHelpLink" src="'. THEME_PATH. '/icons/lock.png" alt="Gesperrt" title="" /></a>';
                            }
                        }

                        //Album angaben
                        if(file_exists($ordner))
                        {
                            echo'<a href="'.$g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$sub_photo_album->getValue('pho_id').'">'.$sub_photo_album->getValue('pho_name').'</a><br />';
                        }
                        else
                        {
                            echo $sub_photo_album->getValue('pho_name');
                        }

                        echo '</li>
                            <li>Fotos: '.$sub_photo_album->countImages().' </li>
                            <li>Datum: '.mysqldate('d.m.y', $sub_photo_album->getValue('pho_begin'));
                            if($sub_photo_album->getValue('pho_end') != $sub_photo_album->getValue('pho_begin'))
                            {
                                echo ' bis '.mysqldate('d.m.y', $sub_photo_album->getValue('pho_end'));
                            }
                            echo '</li> 
    						<li>Fotos von: '.$sub_photo_album->getValue('pho_photographers').'</li>';

                            //bei Moderationrecheten
                            if ($g_current_user->editPhotoRight())
                            {
                                echo '<li>';

                                if(file_exists($ordner))
                                {
	                                echo '
	                                <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photoupload.php?pho_id='.$sub_photo_album->getValue('pho_id').'"><img 
	                                    src="'. THEME_PATH. '/icons/photo_upload.png" alt="Fotos hochladen" title="Fotos hochladen" /></a>

    								<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photo_album_new.php?pho_id='.$sub_photo_album->getValue('pho_id').'&amp;job=change"><img 
                                        src="'. THEME_PATH. '/icons/edit.png" alt="Bearbeiten" title="Bearbeiten" /></a>';
    	                            
                                    if($sub_photo_album->getValue('pho_locked')==1)
    	                            {
    	                                echo '
    	                                <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$sub_photo_album->getValue('pho_id').'&amp;locked=0"><img 
    	                                    src="'. THEME_PATH. '/icons/key.png"  alt="Freigeben" title="Freigeben" /></a>';
    	                            }
    	                            elseif($sub_photo_album->getValue('pho_locked')==0)
    	                            {
    	                                echo '
    	                                <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$sub_photo_album->getValue('pho_id').'&amp;locked=1"><img 
    	                                    src="'. THEME_PATH. '/icons/key.png" alt="Sperren" title="Sperren" /></a>';
    	                            }
                                }

                                echo '
                                <a class="iconLink" href="javascript:deleteObject(\'pho\', \'pho_'.$sub_photo_album->getValue('pho_id').'\','.$sub_photo_album->getValue('pho_id').',\''.$sub_photo_album->getValue('pho_name').'\')"><img 
                                    src="'. THEME_PATH. '/icons/delete.png" alt="Album löschen" title="Album löschen" /></a>
    							</li>';
                            }
                    echo '</ul>
                </dd>
            </dl>
            </li>';
            $counter++;
        }//Ende wenn Ordner existiert
    };//for

    if($counter > 0)
    {
        //Tabellenende
        echo '</ul>';
    }
        
    /****************************Leeres Album****************/
    //Falls das Album weder Fotos noch Unterordner enthaelt
    if(($photo_album->getValue('pho_quantity')=='0' || strlen($photo_album->getValue('pho_quantity')) == 0) && $albums<1)  // alle vorhandenen Albumen werden ignoriert
    {
        echo'Dieses Album enthält leider noch keine Fotos.';
    }
    
    if($g_db->num_rows($result_list) > 2)
    {
        // Navigation mit Vor- und Zurueck-Buttons
        // erst anzeigen, wenn mehr als 2 Eintraege (letzte Navigationsseite) vorhanden sind
        $base_url = $g_root_path.'/adm_program/modules/photos/photos.php?pho_id='.$pho_id;
        echo generatePagination($base_url, $albums-$ignored, 10, $album_element, TRUE);
    }
echo '</div>';

/************************Buttons********************************/
if($photo_album->getValue('pho_id') > 0)
{
    echo '
    <ul class="iconTextLinkList">
        <li>
            <span class="iconTextLink">
                <a href="'.$g_root_path.'/adm_program/system/back.php"><img 
                src="'. THEME_PATH. '/icons/back.png" alt="Zurück" /></a>
                <a href="'.$g_root_path.'/adm_program/system/back.php">Zurück</a>
            </span>
        </li>
    </ul>';
}

/***************************Seitenende***************************/

require(THEME_SERVER_PATH. '/overall_footer.php');

?>