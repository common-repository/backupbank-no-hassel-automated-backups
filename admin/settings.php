<?php
    global $backupbank_plugin_version, $wpdb;

    $_success   = 0;
    $_error     = 0;
    $_warning   = 0;
    $table_name = $wpdb->prefix.'backupbank_settings';
    if (isset($_POST['update']) && wp_verify_nonce($_POST['_csrf'],'bb-csrf')) {
        if (isset($_POST['enabled'])) {
            $enabled = preg_replace('/[^0-9]/', '', $_POST['enabled']);
        } else {
            $enabled = 0;
        }
        $backup_schedule = sanitize_text_field($_POST['schedule']);
        $retention = sanitize_text_field($_POST['retention']);
        $backup_type = sanitize_text_field($_POST['backup_type']);
        $sftp_scp_host = sanitize_text_field($_POST['sftp_scp_host']);
        $sftp_scp_user = sanitize_text_field($_POST['sftp_scp_user']);
        $sftp_scp_folder = sanitize_text_field($_POST['sftp_scp_folder']);
        $sftp_scp_pass = openssl_encrypt($_POST['sftp_scp_pass'], 'aes-256-cbc', '!B@ckupB@nk2^&', 0);
        $site_name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', get_bloginfo( 'name' )), '-'));

        if (!empty($license_key)) {
            $bb = new BackupBank_Backup();
            if (!$bb->validate_key($license_key)) {
                $license_key = '';
                $_error = 3;
                $_error_desc = 'Your license key is invalid or has expired';
            }
        }

        // Update schedule
        $remove_schedule = 'daily';
        $timestamp = wp_next_scheduled( 'bl_cron_hook' );
        if ($backup_schedule == 'daily') {
            $remove_schedule = 'weekly';
        }
        wp_unschedule_event($timestamp, 'bl_cron_hook');

        $wpdb->query('DELETE FROM '.$table_name);

        $sql = 'INSERT INTO '.$table_name.' SET '.
                'enabled = "'.$enabled.'", '.
                'schedule = "'.$backup_schedule.'", '.
                'retention = "'.$retention.'", '.
                'backup_path = "'.get_home_path().'", '.
                'backup_type = "'.$backup_type.'", '.
                'sftp_scp_host = "'.$sftp_scp_host.'", '.
                'sftp_scp_user = "'.$sftp_scp_user.'", '.
                'sftp_scp_pass = "'.$sftp_scp_pass.'", '.
                'sftp_scp_folder = "'.$sftp_scp_folder.'", '.
                'website_name = "'.$site_name.'" '.
                '';
        $wpdb->query($sql);

        $_success = 1;
    } else if (isset($_POST['run_backup'])) {
        $bb_backup = new BackupBank_Backup();
        $response = $bb_backup->runBackup();
        if (!empty($response) && $response['status']) {
            $_success = 2;
        } else {
            $_error = 3;
            $_error_desc = $response['error'];
        }
    }

    // get server key data
    $_enabled       = 0;
    $_backup_type    = 'sftp_scp';
    $_license_key    = '';
    $_backup_schedule = 'daily';
    $_retention = '5';
    $sql = 'SELECT * FROM '.$table_name;
    $results = $wpdb->get_results($sql);
    foreach ($results as $result) {
        $_enabled       = $result->enabled;
        $_sftp_scp_host = $result->sftp_scp_host;
        $_sftp_scp_user = $result->sftp_scp_user;
        $_sftp_scp_folder = $result->sftp_scp_folder;
        $_sftp_scp_pass = openssl_decrypt($result->sftp_scp_pass, 'aes-256-cbc', '!B@ckupB@nk2^&', 0);
        $_backup_schedule = $result->schedule;
        $_retention = $result->retention;

        if ($_enabled != 1) {
            $_warning = 1;
        }
    }

    wp_enqueue_style('backupbank_admin_css', BACKUPBANK_PLUGIN_URL.'admin/css/style.css');
    wp_enqueue_script('backupbank_admin_js', BACKUPBANK_PLUGIN_URL.'admin/js/scripts.js');
?>
<!--//Wrapper //-->
<div id="bbank">
    <h2><img src="<?php echo esc_html(BACKUPBANK_PLUGIN_URL);?>admin/images/backupbank_logo.svg" alt="BackupBank" style="width: 180px; height: auto;" /></h2>

    <p/>
    <div style="display: flex; flex-direction: row; justify-content: flex-start; ">
        <div style="padding: 0 1em 1em 0;">
            <a style="font-size: 1.2em; line-height: 1.3em;" href="https://www.backupbank.io" target="_blank">BackupBank.io</a>
        </div>
        <div style="padding: 0 1em 1em 0;">
            |
        </div>
        <div style="padding: 0 1em 1em 0;">
            <span style="font-size: 1.2em; line-height: 1.3em;">WordPress Plugin v<?php echo esc_html($backupbank_plugin_version);?></span>
        </div>
    </div>
    <div class="contents">
         <h4 class="icon bb">Use BackupBank to easily and securely backup your website property to your own storage.</h4>
    </div>

    <form method="post">
    <input name="_csrf" type="hidden" value="<?php echo wp_create_nonce('bb-csrf'); ?>" />

    <?php
        if ($_success == 1) {
            ?>
            <div class="banner success">Success: settings have been updated</div>
            <?php
        } else if ($_success == 2) {
            ?>
            <div class="banner success">Success: Backup has been completed successfully</div>
            <?php
        }
        if ($_error == 2) {
            ?>
            <div class="banner">Error: please check all required fields below</div>
            <?php
        }
        if ($_error == 3) {
            ?>
            <div class="banner">Error: <?php echo esc_html($_error_desc); ?></div>
            <?php
        }
        if ($_warning == 1) {
            ?>
            <div class="banner">Warning: Backups are disabled</div>
            <?php
        }
        if (defined('DISABLE_WP_CRON') && constant('DISABLE_WP_CRON') != 1) {
            ?>
            <div class="banner">Warning: DISABLE_WP_CRON is not been enabled, learn why this is <u>extremely important</u>, and how to configure it properly on our website <a href="https://www.backupbank.io/documentation" target="_blank">documentation</a></div>
            <?php
        }
    ?>

    <div class="contents backupbank-premium">
        <div class="premium-intro">
            <h2>BackupBank Premium offers automatic backups to Google Cloud and Dropbox</h2>
            <p>&rarr;&nbsp;If you would like to expand your BackupBank functionality, including granular retention and expanded scheduling: <a href="https://www.backupbank.io/wordpress-backup-plugin"><strong>Go Premium</strong></a></p>
        </div>
        <div class="backupbank-brands-container">
            <div class="backupbankitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 324 63.8">
                  <path d="m37.6,12l-18.8,12,18.8,12-18.8,12L0,35.9l18.8-12L0,12,18.8,0l18.8,12Zm-18.9,39.8l18.8-12,18.8,12-18.8,12-18.8-12Zm18.9-15.9l18.8-12-18.8-11.9L56.3,0l18.8,12-18.8,12,18.8,12-18.8,12-18.7-12.1Z" style="fill: #fff;"/>
                  <path d="m89.8,12h15.2c9.7,0,17.7,5.6,17.7,18.4v2.7c0,12.9-7.5,18.7-17.4,18.7h-15.49999V12Zm8.5,7.2v25.3h6.5c5.5,0,9.2-3.6,9.2-11.6v-2.1c0-8-3.9-11.6-9.5-11.6h-6.2Zm28.89999.4h6.8l1.10001,7.5c1.3-5.1,4.60001-7.8,10.60001-7.8h2.10001v8.6h-3.5c-6.89999,0-8.60001,2.4-8.60001,9.2v14.8h-8.4V19.6h-.10001Zm22.3,16.8v-.9c0-10.8,6.89999-16.7,16.3-16.7,9.60001,0,16.3,5.9,16.3,16.7v.9c0,10.6-6.5,16.3-16.3,16.3-10.40001-.1-16.3-5.7-16.3-16.3Zm24-.1v-.8c0-6-3-9.6-7.8-9.6-4.7,0-7.8,3.3-7.8,9.6v.8c0,5.8,3,9.1,7.8,9.1,4.8-.1,7.8-3.3,7.8-9.1Zm13-16.7h7l.8,6.1c1.7-4.1,5.3-6.9,10.60001-6.9,8.2,0,13.60001,5.9,13.60001,16.8v.9c0,10.6-6,16.2-13.60001,16.2-5.10001,0-8.60001-2.3-10.3-6v16.3h-8.2l.09999-43.4h0Zm23.5,16.7v-.7c0-6.4-3.3-9.6-7.7-9.6-4.7,0-7.8,3.6-7.8,9.6v.6c0,5.7,3,9.3,7.7,9.3,4.8-.09999,7.8-3.2,7.8-9.2Zm20.89999,9.6l-.7,5.9h-7.2V8.8h8.2v16.5c1.8-4.2,5.39999-6.5,10.5-6.5,7.7.1,13.39999,5.4,13.39999,16.1v1c0,10.7-5.39999,16.8-13.60001,16.8-5.39998-.1-8.89998-2.6-10.59999-6.8Zm15.60001-10v-.8c0-5.9-3.2-9.2-7.7-9.2-4.60001,0-7.8,3.7-7.8,9.3v.7c0,6,3.10001,9.5,7.7,9.5,4.90001,0,7.8-3.1,7.8-9.5Zm12.20001.5v-.9c0-10.8,6.89999-16.7,16.29999-16.7,9.60001,0,16.29999,5.9,16.29999,16.7v.9c0,10.6-6.60001,16.3-16.29999,16.3-10.39999-.1-16.29999-5.7-16.29999-16.3Zm24.09998-.1v-.8c0-6-3-9.6-7.79999-9.6-4.70001,0-7.79999,3.3-7.79999,9.6v.8c0,5.8,3,9.1,7.79999,9.1,4.79999-.1,7.79999-3.3,7.79999-9.1Zm19.5-1.2l-11.29999-15.5h9.70001l6.5,9.7,6.60001-9.7h9.60001l-11.50003,15.4,12.10001,16.8h-9.5l-7.39999-10.7-7.20001,10.7h-9.89999l12.29999-16.7Z" style="fill: #fff; opacity: .7;"/>
                </svg>
            </div>
            <div class="backupbankitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 176.87469 27.70961">
                    <path d="m20.80469,7.26608h1l2.85-2.85.14-1.21C19.4905-1.47558,11.39538-.97093,6.71371,4.33326c-1.30045,1.47337-2.24447,3.22619-2.75902,5.12282.3175-.13014.66921-.15124,1-.06l5.7-.94s.29-.48.44-.45c2.5355-2.78463,6.80253-3.10916,9.73-.74h-.02Z" style="fill: #ea4335;"/>
                    <path d="m28.71469,9.45608c-.65509-2.41237-2.00009-4.58106-3.87-6.24l-4,4c1.68906,1.38013,2.65118,3.45918,2.61,5.64v.71c1.96613,0,3.56,1.59387,3.56,3.56s-1.59387,3.56-3.56,3.56h-7.12l-.71.72v4.27l.71.71h7.12c5.114.03981,9.292-4.07363,9.33181-9.18763.02413-3.09963-1.50424-6.00575-4.07181-7.74237Z" style="fill: #4285f4;"/>
                    <path d="m9.20469,26.34608h7.12v-5.7h-7.12c-.50727-.00011-1.00859-.10924-1.47-.32l-1,.31-2.87,2.85-.25,1c1.60943,1.21531,3.57328,1.86875,5.59,1.86Z" style="fill: #34a853;"/>
                    <path d="m9.20469,7.85608C4.09062,7.88663-.03038,12.05716.00017,17.17123c.01706,2.85573,1.35082,5.54384,3.61452,7.28485l4.13-4.13c-1.79175-.80951-2.58802-2.91825-1.77851-4.71s2.91825-2.58802,4.71-1.77851c.78949.35669,1.42182.98902,1.77851,1.77851l4.13-4.13c-1.75721-2.29723-4.48778-3.64031-7.38-3.63Z" style="fill: #fbbc05;"/>
                    <path d="m51.74469,22.36608c-2.47695.03156-4.85994-.94692-6.6-2.71-1.79596-1.68931-2.80083-4.05458-2.77-6.52-.0296-2.46517.97507-4.82997,2.77-6.52,1.73163-1.78145,4.1157-2.77842,6.6-2.76,2.36626-.03154,4.64637.88699,6.33,2.55l-1.78,1.81c-1.21898-1.17805-2.85498-1.82526-4.55-1.8-1.77779-.02184-3.48307.70381-4.7,2-1.26401,1.25399-1.96115,2.96976-1.93,4.75-.01483,1.75951.68106,3.45055,1.93,4.69,2.57505,2.56619,6.72536,2.61496,9.36.11.7844-.85901,1.26562-1.95143,1.37-3.11h-6v-2.56h8.49c.08343.51557.11692,1.03799.1,1.56.1067,2.18327-.68461,4.31511-2.19,5.9-1.67514,1.74407-4.01318,2.69311-6.43,2.61Zm19.74-1.7c-2.36735,2.26927-6.10265,2.26927-8.47,0-1.14661-1.11174-1.7742-2.65353-1.73-4.25-.04296-1.59625.58443-3.13756,1.73-4.25,2.36941-2.26414,6.10059-2.26414,8.47,0,1.14556,1.11244,1.77297,2.65375,1.73,4.25.0416,1.59798-.58972,3.13999-1.74001,4.25h.01Zm-6.6-1.67c1.22308,1.30615,3.27343,1.3735,4.57958.15042.05177-.04848.10194-.09865.15042-.15042.66759-.68998,1.0282-1.62034,1-2.58.02977-.9629-.33086-1.89695-1-2.59-1.2574-1.31168-3.34005-1.35568-4.65173-.09828-.03345.03206-.06621.06483-.09827.09828-.66914.69305-1.02977,1.6271-1,2.59-.02554.9611.33875,1.89167,1.00999,2.58h.01Zm19.62,1.67c-2.36735,2.26927-6.10265,2.26927-8.47,0-1.14661-1.11174-1.77421-2.65353-1.73-4.25-.04297-1.59625.58444-3.13756,1.73-4.25,2.36735-2.26927,6.10265-2.26927,8.47,0,1.14556,1.11244,1.77297,2.65375,1.73,4.25.0442,1.59647-.5834,3.13826-1.73,4.25Zm-6.6-1.67c1.22308,1.30615,3.27343,1.3735,4.57958.15042.05177-.04848.10194-.09865.15042-.15042.66759-.68998,1.0282-1.62034,1-2.58.02977-.9629-.33086-1.89695-1-2.59-1.2574-1.31168-3.34005-1.35568-4.65173-.09828-.03345.03206-.06621.06483-.09827.09828-.66914.69305-1.02977,1.6271-1,2.59-.02554.9611.33875,1.89167,1.00999,2.58h.01Zm15.16,8.71c-1.19859.03812-2.37399-.33603-3.33-1.06-.84883-.63812-1.51733-1.4858-1.94-2.46l2.28-.95c.24813.5822.63631,1.09404,1.13,1.49.52843.42598,1.19154.64937,1.87.63.87057.05296,1.72108-.27557,2.33-.9.60247-.72673.89996-1.65861.83-2.6v-.86h-.09c-.77263.91766-1.93232,1.41784-3.13,1.35-1.51793.00459-2.96848-.6264-4-1.74-1.11876-1.10422-1.73669-2.61831-1.71-4.19-.0279-1.58074.58958-3.10458,1.71-4.22,1.02903-1.11832,2.48029-1.75324,4-1.75.63611-.00111,1.26454.13892,1.84.41.49782.2174.94166.54174,1.3.95h.09v-.95h2.48v10.65c.13194,1.70269-.44315,3.38457-1.59,4.65-1.09196,1.04466-2.55978,1.60365-4.07001,1.55Zm.18-7.68c.86449.019,1.69266-.34744,2.26-1,.617-.70706.94228-1.62214.91-2.56.03704-.95368-.28805-1.88608-.91-2.61-.56649-.65371-1.39523-1.02041-2.26-1-.88742-.01822-1.73983.34605-2.34,1-.66148.69791-1.02084,1.62865-1,2.59-.02055.95239.33934,1.8737,1,2.56.59733.66026,1.44971,1.03182,2.34,1.02Zm9.89-15.5v17.48h-2.61V4.52608h2.61Zm7.16,17.84c-1.58013.04062-3.10573-.57904-4.21-1.71-1.12016-1.1223-1.73102-2.65488-1.69-4.24-.05975-1.58871.52857-3.13351,1.63-4.28,1.03873-1.09502,2.49101-1.70134,4-1.67.68624-.00725,1.36676.12545,2,.39.5704.23023,1.09019.56996,1.53,1,.37765.36456.71309.77045,1,1.21.23206.37112.42956.76276.59,1.17l.27.68-8,3.29c.49527,1.13795,1.6401,1.85346,2.88,1.8,1.1988.00327,2.31126-.62321,2.93-1.65l2,1.35c-.5104.73021-1.16355,1.34935-1.92,1.82-.89958.56836-1.9461.86041-3.00999.84Zm-3.34-6.13l5.32-2.21c-.16392-.38967-.45701-.71102-.83-.91-.41765-.23585-.89041-.35663-1.37-.35-.81383.02108-1.58646.36247-2.15.95-.68282.65341-1.03848,1.5774-.96999,2.52Zm22.13,6.13c-4.55015.07854-8.30244-3.54641-8.38099-8.09656-.00191-.11114-.00159-.22231.00099-.33344-.10561-4.5496,3.49696-8.3234,8.04655-8.42901.11113-.00258.22231-.00291.33345-.00099,2.27599-.07116,4.45984.90108,5.92999,2.64l-1.44,1.4c-1.0806-1.35915-2.74507-2.1208-4.48-2.05-1.66385-.03748-3.271.60537-4.45,1.78-1.22639,1.23322-1.88245,2.9223-1.81,4.66-.07245,1.7377.58361,3.42678,1.81,4.66,1.179,1.17463,2.78615,1.81749,4.45,1.78,1.93596.02169,3.77638-.83962,5-2.34l1.44,1.44c-.75397.90159-1.70053,1.62261-2.77,2.11-1.1528.53223-2.4104.79879-3.67999.78Zm10.31999-.36h-2.07001V5.85608h2.07001v16.15Zm3.38-9.72c2.25786-2.24234,5.90215-2.24234,8.16,0,1.0713,1.1311,1.6472,2.6428,1.60001,4.2.0472,1.5572-.5287,3.0689-1.60001,4.2-2.25786,2.24234-5.90215,2.24234-8.16,0-1.0713-1.1311-1.6472-2.6428-1.60001-4.2-.0472-1.5572.5287-3.0689,1.60001-4.2Zm1.53999,7.1c1.32478,1.40004,3.53368,1.46106,4.93373.13628.04666-.04416.09212-.08961.13628-.13628.73085-.78423,1.11635-1.82901,1.07001-2.9.04634-1.07099-.33916-2.11577-1.07001-2.9-1.32478-1.40004-3.53368-1.46106-4.93373-.13628-.04666.04416-.09212.08961-.13628.13628-.73085.78423-1.11635,1.82901-1.07001,2.9-.04094,1.069.34804,2.10985,1.08002,2.89l-.01001.01Zm19.69,2.62h-2v-1.53h-.06c-.35596.58111-.85979,1.05732-1.46001,1.38-.62285.36098-1.33011.55073-2.05.55-1.18408.081-2.34035-.38298-3.14-1.26-.75754-.95815-1.13815-2.16046-1.07001-3.38v-6.8h2.07001v6.42c0,2.06.91,3.09,2.73,3.09.82047.02558,1.60272-.34691,2.10001-1,.53653-.67996.82257-1.52394.81-2.39v-6.12h2.07001v11.04Zm7.03.36c-1.4211.00472-2.77307-.6128-3.7-1.69-1.03676-1.14507-1.59195-2.64588-1.55-4.19-.04195-1.54412.51324-3.04493,1.55-4.19.92693-1.0772,2.2789-1.69472,3.7-1.69.7867-.01242,1.56389.17328,2.25999.54.61169.30781,1.12967.774,1.5,1.35h.09l-.09-1.53v-5.11h2.07001v16.15h-2v-1.53h-.09c-.37033.576-.88831,1.04219-1.5,1.35-.69014.36347-1.46005.54907-2.24001.54Zm.34-1.89c.94649.01378,1.85332-.37954,2.49001-1.08.70319-.80067,1.06248-1.84622,1-2.91.06248-1.06378-.29681-2.10933-1-2.91-1.2563-1.38071-3.39401-1.48157-4.77473-.22527-.07858.07151-.15376.14668-.22527.22527-.70547.79536-1.06531,1.83885-1,2.9-.06531,1.06115.29453,2.10464,1,2.9.63766.71316,1.55344,1.11449,2.50999,1.1Z" style="fill: #fff;"/>
                </svg>
            </div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 175 152.16764" class="premium-bank">
            <path id="b" data-name="c" d="m112.86471,22.81177l39.32353,39.32353-27.91765,27.91765-11.40589,11.40588-27.91764,27.89706-62.13529-62.13529,11.40588-11.40588,22.81176,22.81177,16.49118-16.49118-22.79118-22.81177,11.40588-11.40588,22.81176,22.81177,27.91765-27.91765m0,55.8147l16.49117-16.49118-16.49117-16.49118-16.49118,16.49118,16.49118,16.49118m-27.91765,27.91765l16.49118-16.49118-4.09706-4.09706-12.39412-12.39412-16.49118,16.49118,16.49118,16.49118M112.86471,0l-11.40588,11.40588-16.49118,16.49118-11.40588-11.40588-11.40588-11.40588-11.40588,11.40588-11.42648,11.42647-11.40588,11.40588h0l-5.08529,5.08529-11.40588,11.40588L0,67.22059l11.40588,11.40588,62.13529,62.13529,11.40588,11.40589,11.40588-11.40589,27.91765-27.91764,11.40589-11.40588,27.91764-27.91765,11.40589-11.40588-11.40589-11.40588L124.27058,11.38529,112.86471,0h0Z" style="fill:#fff; opacity:.2;"/>
        </svg>
        <div class="backuptop-line"></div>
        <svg  class="backuptop-curve" fill="none" height="135" width="205" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 205 135">
            <path d="M.00226,.50008s43.51961-.19654,74.79843,18.85214c52.48973,31.96606,43.52481,114.51478,128.52548,114.51478" stroke="#ffffff"></path>
        </svg>
    </div>

    <div class="contents">
        <h3 class="icon opt">Plugin Options</h3>
        <div class="basic-module">
            <div class="inside padded bot-0 white-bg">
                <div class="switch">
                    <input type="checkbox" id="enabled" name="enabled" value="1" class="switch__input" onchange="toggleOnOff(this)" <?php echo $_enabled ? 'checked' : '';?>>
                    <label for="enabled" class="switch__label">Turn On Plugin (Activate Backups)</label>
                </div>
            </div>
            <div class="inside padded white-bg" style="border-top:1px solid #d3d1e3;">
                <label for="backup-time">Choose Back-up Schedule</label>
                <label class="control control--radio">Nightly
                    <input type="radio" name="schedule" value="daily" <?php echo $_backup_schedule == 'daily' ? 'checked' : '';?>/>
                    <div class="control__indicator"></div>
                </label>
                <label class="control control--radio">Weekly
                  <input type="radio" name="schedule" value="weekly" <?php echo $_backup_schedule == 'weekly' ? 'checked' : '';?> />
                  <div class="control__indicator"></div>
                </label>
            </div>

            <div class="inside padded white-bg" style="border-top:1px solid #d3d1e3;">
                <label for="backup-retention">Choose Back-up Retention</label>
                <div class="select">
                    <select name="retention" required>
                        <option value="">Choose Option</option>
                        <option value="5" <?php echo ($_retention == '5' ? 'selected' : ''); ?>>5 copies</option>
                        <option value="10" <?php echo ($_retention == '10' ? 'selected' : ''); ?>>10 copies</option>
                    </select>
                    <div class="select__arrow"></div>
                </div>
            </div>

            <!--// SFTP //-->
            <div class="inside padded dark-bg" style="border-top:1px solid #d3d1e3; display: <?php echo ($_backup_type == 'sftp_scp' ? 'block' : 'none'); ?>;" id="sftp_scp">
                <label for="sftp-host">SFTP / SCP Host</label>
                <input type="text" name="sftp_scp_host" value="<?php echo esc_textarea($_sftp_scp_host);?>" size="40">
                <br>
                <label for="sftp-user">SFTP / SCP Username</label>
                <input type="text" name="sftp_scp_user" value="<?php echo esc_textarea($_sftp_scp_user);?>" size="40">
                <br>
                <label for="sftp-pass">SFTP / SCP Password</label>
                <input type="password" name="sftp_scp_pass" value="<?php echo esc_textarea($_sftp_scp_pass);?>"  size="100">
                <br>
                <label for="sftp-pass">SFTP / SCP Folder Path</label>
                <input type="text" name="sftp_scp_folder" value="<?php echo esc_textarea($_sftp_scp_folder);?>"  size="40" placeholder="( Optional )">
            </div>
            <!--// END SFTP //-->
        </div>
        <input type="submit" name="update" value="Save / Update" class="btn" />
        <input type="submit" name="run_backup" value="Run Backup" class="btn run" />
        <div style="clear:both;"></div>
    </div>
    </form>

</div>
<!--//END Wrapper //-->