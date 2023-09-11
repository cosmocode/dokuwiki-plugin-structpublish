<?php
/**
 * Italian language file for structpublish plugin
 *
 * @author Anna Dabrowska <dokuwiki@cosmocode.de>
 */

// menu entry for admin plugins
$lang['menu'] = 'Pubblicazione di dati strutturati';

// banner
$lang['version'] = 'Versione';
$lang['newversion'] = 'Nuova versione';
$lang['status'] = 'Stato di pubblicazione della pagina visualizzata';
$lang['actions'] = 'Azioni';
$lang['status_draft'] = 'Bozza';
$lang['status_approved'] = 'Approvata';
$lang['status_published'] = 'Pubblicata';
$lang['action_approve'] = 'Approva';
$lang['action_publish'] = 'Pubblica';

$lang['diff'] = 'Mostra le differenze';
$lang['banner_status_draft'] = 'Questa revisione è una <strong>bozza</strong>, creata da {revision}.';
$lang['banner_status_approved'] = 'Questa revisione è stata <strong>approvata per la pubblicazione</strong> il {datetime} da {user}.';
$lang['banner_status_published'] = 'Questa revisione è stata <strong>pubblicata <span class="plugin-structpublish-version"> come versione "{version}"</span></strong> il {datetime} da {user}.';
$lang['banner_latest_publish'] = 'L\'ultima revisione pubblicata è la versione {version} rilasciata da {user} il {datetime}.';
$lang['banner_previous_publish'] = 'Questa pagina è stata precedentemente pubblicata come versione {version} da {user} il {datetime}.';
$lang['banner_latest_draft'] = 'Esiste una bozza più recente creata da {revision}.';
$lang['compact_banner_status_draft'] = 'Bozza';
$lang['compact_banner_status_approved'] = 'Approvata';
$lang['compact_banner_status_published'] = 'Pubblicata come versione "{version} il {datetime} da {user}"';
$lang['compact_banner_latest_publish'] = '';
$lang['compact_banner_previous_publish'] = '';
$lang['compact_banner_latest_draft'] = 'Bozza più recente: {revision}';

// admin
$lang['assign_pattern'] = 'Pattern';
$lang['assign_user'] = 'Utente o @gruppo';
$lang['assign_status'] = 'Stato';
$lang['assign_add'] = 'Aggiungi';
$lang['assign_del'] = 'Rimuovi';

// email
$lang['email_subject'] = 'Lo stato di pubblicazione di una pagina della wiki è stato modificato';
$lang['email_error_norecipients'] = 'Gli indirizzi dei destinatari della notifica non sono stati trovati!';
