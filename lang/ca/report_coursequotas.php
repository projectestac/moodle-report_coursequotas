<?php

$string['pluginname'] = 'Ocupació del disc';
$string['coursequotas'] = 'Ocupació del disc';
$string['total_noquota_description'] = 'Ocupació del disc';
$string['coursequotas:view'] = 'Veure el consum de quota dels cursos';
$string['total_data'] = 'Total';
$string['disk_used'] = 'Espai ocupat';
$string['disk_free'] = 'Espai lliure';
$string['disk_used_other'] = 'Espai ocupat en altres';
$string['disk_used_course'] = 'Espai ocupat en cursos';
$string['disk_used_backup'] = 'Espai ocupat en backups';
$string['disk_used_temp'] = 'Espai ocupat en fitxers temporals';
$string['disk_used_trash'] = 'Espai ocupat en fitxers trash';
$string['disk_used_repo'] = 'Espai ocupat al repositori';
$string['disk_used_user'] = 'Espai ocupat en fitxers d\'usuari';
$string['category_data'] = 'Categories';
$string['larger_courses'] = 'Cursos';
$string['course_name'] = 'Nom del curs';
$string['category_name'] = 'Nom de la categoria';
$string['front_page'] = 'Pàgina inicial';
$string['disk_consume_explain'] = 'Actualment s\'estan utilitzant <strong>{$a->diskConsume} MB</strong> dels <strong>{$a->diskSpace} MB</strong> disponibles, dels quals, aproximadament:';
$string['disk_consume_repofiles'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats en repositoris del sistema de fitxers';
$string['disk_consume_courses'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats als cursos (sense comptar les còpies de seguretat)';
$string['disk_consume_backups'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats a les còpies de seguretat, incloses les dels cursos i les dels usuaris';
$string['disk_consume_user'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats en fitxers d\'usuari';
$string['disk_consume_temp'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats a la carpeta <em>temp</em> (fitxers temporals pendents de ser esborrats pel cron)';
$string['disk_consume_trash'] = '<strong>{$a->figure} {$a->unit}</strong> utilitzats a la carpeta <em>trashdir</em> (fitxers marcats com a esborrats i pendents de ser esborrats pel cron)';
$string['total_description'] = 'Percentatge de disc ocupat en relació amb el total de la quota assignada';
$string['category_description'] = 'Mida total de les categories i subcategories calculada a partir de la mida dels cursos que contenen';
$string['courses_description'] = 'Llista de tots els cursos, ordenada de major a menor segons la mida dels seus fitxers';

$string['filemanager'] = 'Gestor de fitxers';
$string['manage'] = 'Gestiona els fitxers';
$string['filearea'] = 'Àrea de fitxers';
$string['component'] = 'Component';
$string['owner'] = 'Propietari';
$string['context'] = 'Context';
$string['totalfilesize'] = 'Ocupació de tots els fitxers: {$a}';
$string['realfilesize'] = 'Ús real de disc: {$a}';
$string['nofilesfound'] = 'No s\'ha trobat cap fitxer';
$string['showingfiles'] = 'Mostrant {$a->files} de {$a->total}';
$string['addchildren'] = 'Afegir els contextes fill';
$string['allusers'] = 'Tots els usuaris';
$string['allfileareas'] = 'Totes les àrees de fitxer';
$string['allcomponents'] = 'Tots els components';
$string['more_than'] = 'Més de';
$string['less_than'] = 'Menys de';
$string['showonlybackups'] = 'Mostra només els fitxers de còpia de seguretat';
$string['hidesamehash'] = 'Amaga els fitxers amb el mateix contenthash (Mode expert)';
$string['viewsimilarfiles'] = 'Mostra fitxers similars';
$string['manage_backup_files'] = 'Si voleu alliberar espai podeu eliminar les còpies de seguretat dels cursos des d\'<a href="{$a}">aquest apartat</a>';