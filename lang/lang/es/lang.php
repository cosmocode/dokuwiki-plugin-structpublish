<?php
/**
 * Archivo de idioma en español para el plugin structpublish
 *
 */

// entrada del menú para plugins de administración
$lang['menu'] = 'Datos estructurados de publicación';

// banner
$lang['version'] = 'Versión';
$lang['newversion'] = 'Nueva versión';
$lang['status'] = 'Estado de publicación de la página que está viendo actualmente';
$lang['actions'] = 'Acciones';
$lang['status_draft'] = 'Borrador';
$lang['status_approved'] = 'Aprobado';
$lang['status_published'] = 'Publicado';
$lang['status_na'] = 'N/D';
$lang['action_approve'] = 'Aprobar';
$lang['action_publish'] = 'Publicar';

$lang['diff'] = 'Mostrar diferencias';
$lang['banner_status_draft'] = 'Esta revisión de la página es un <strong>borrador en progreso</strong>, creado el {revision}.';
$lang['banner_status_approved'] = 'Esta revisión de la página ha sido <strong>aprobada para su publicación</strong> el {datetime} por {user}.';
$lang['banner_status_published'] = 'Esta revisión de la página ha sido <strong>publicada <span class="plugin-structpublish-version">como versión "{version}"</span></strong> el {datetime} por {user}.';
$lang['banner_latest_publish'] = 'La página fue publicada más recientemente como versión {version} por {user} el {datetime}.';
$lang['banner_previous_publish'] = 'La página fue publicada anteriormente como versión {version} por {user} el {datetime}.';
$lang['banner_latest_draft'] = 'Existe un borrador más reciente creado el {revision}.';
$lang['compact_banner_status_draft'] = 'Borrador';
$lang['compact_banner_status_approved'] = 'Aprobado';
$lang['compact_banner_status_published'] = 'Publicado como versión "{version}" el {datetime} por {user}';
$lang['compact_banner_latest_publish'] = '';
$lang['compact_banner_previous_publish'] = '';
$lang['compact_banner_latest_draft'] = 'Borrador más reciente: {revision}';

// administración
$lang['assign_pattern'] = 'Patrón';
$lang['assign_user'] = 'Usuario o @grupo';
$lang['assign_status'] = 'Estado';
$lang['assign_add'] = 'Añadir asignación';
$lang['assign_del'] = 'Eliminar asignación';

// correo electrónico
$lang['email_subject'] = 'El estado de publicación de una página wiki ha cambiado';
$lang['email_error_norecipients'] = '¡No se encontraron destinatarios para notificar!';
