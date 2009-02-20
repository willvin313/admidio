<?php
/******************************************************************************
 * Konfigurationsdatei fuer Admidio-Plugin Sidebar-Online
 *
 * Copyright    : (c) 2004 - 2008 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Thomas Thoss
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

// Zeit in der die User noch als Aktiv gelten (Default = 10)
$plg_time_online = 10;

// Sollen Besucher (nicht eingeloggte Benutzer) auch angezeigt werden
// 0 = nur eingeloggte Mitglieder werden angezeigt
// 1 = (Default) Anzahl der Besucher wird aufgelistet
$plg_show_visitors = 1;

// soll das eigene Login angezeigt werden
// 0 = das eigene Login (auch ausgeloggt) wird nicht angezeigt
// 1 = (Default) das eigene Login (auch ausgeloggt) wird angezeigt
$plg_show_self = 1;

// Anzeige der Benutzernamen untereinander bzw. nebeneinander
// 0 = (Default) Benutzernamen untereinander auflisten (1 Name pro Zeile)
// 1 = Benutzernamen nebeneinander auflisten
$plg_show_users_side_by_side = 0;

// Name einer CSS-Klasse fuer Links
// Nur noetig, falls die Links ein anderes Aussehen bekommen sollen
$plg_link_class = '';

// Angabe des Ziels (target) in dem die Inhalte der Links geöffnet werden sollen
// Hier koennen die ueblichen targets (_self, _top ...) oder Framenamen angegeben werden
$plg_link_target = '_self';

//Text der über den angezeigten Benutzern steht (Default = "Online sind:<br />"
$plg_online_text = "Online sind:<br />";

?>