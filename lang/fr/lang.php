<?php
/**
 * french language file for structpublish plugin
 *
 * @author Josquin DEHAENE <josquin@moka.works>
 */

// entrée du menu pour les plugins d'administration
$lang['menu'] = 'Données de publication structurées';

// banner
$lang['version'] = 'Version';
$lang['newversion'] = 'Nouvelle version';
$lang['status'] = 'Statut de publication de la page que vous consultez actuellement';
$lang['actions'] = 'Actions';
$lang['status_draft'] = 'Brouillon';
$lang['status_approved'] = 'Approuvé';
$lang['status_published'] = 'Publié';
$lang['action_approve'] = 'Approuver';
$lang['action_publish'] = 'Publier';

$lang['diff'] = 'Afficher les différences';
$lang['banner_status_draft'] = 'Cette révision de la page est un <strong>brouillon en cours</strong>, créé le {revision}.';
$lang['banner_status_approved'] = 'Cette révision de la page a été <strong>approuvée pour publication</strong> le {datetime} par {user}.';
$lang['banner_status_published'] = 'Cette révision de la page a été <strong>publiée <span class="plugin-structpublish-version">en tant que version "{version}"</span></strong> le {datetime} par {user}.';
$lang['banner_latest_publish'] = 'La page a été publiée pour la dernière fois en tant que version {version} par {user} le {datetime}.';
$lang['banner_previous_publish'] = 'La page a été précédemment publiée en tant que version {version} par {user} le {datetime}.';
$lang['banner_latest_draft'] = 'Un brouillon plus récent a été créé le {revision}.';
$lang['compact_banner_status_draft'] = 'Brouillon';
$lang['compact_banner_status_approved'] = 'Approuvé';
$lang['compact_banner_status_published'] = 'Publié en tant que version "{version}" le {datetime} par {user}';
$lang['compact_banner_latest_publish'] = '';
$lang['compact_banner_previous_publish'] = '';
$lang['compact_banner_latest_draft'] = 'Brouillon plus récent : {revision}';

// admin
$lang['assign_pattern'] = 'Modèle';
$lang['assign_user'] = 'Utilisateur ou @group';
$lang['assign_status'] = 'Statut';
$lang['assign_add'] = 'Ajouter une attribution';
$lang['assign_del'] = 'Supprimer une attribution';

// email
$lang['email_subject'] = 'Le statut de publication d’une page wiki a changé';
$lang['email_error_norecipients'] = 'Aucun destinataire trouvé pour la notification !';

