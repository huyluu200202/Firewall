<!doctype html>
<html lang="en-US" class="no-js">
  <head>

    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="robots" content="noindex, nofollow" />
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta name="copyright" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <title><?= (empty($headTitle) ? ('BIF') : ($headTitle)) ?> | <?= $system_hostname ?>.<?= $system_domain ?></title> 
    <?php $theme_name = (empty($ui_theme) ? ('bif') : ($ui_theme)); ?> 

    <!-- Favicon -->
    <link href="<?= view_cache_safe(sprintf('/ui/themes/%s/build/images/favicon.png', $theme_name)) ?>" rel="shortcut icon"> 

    <!--link rel="icon" type="image/png" href="/ui/themes/dynfi/build/images/favicon.png"-->

    <!-- css imports -->
    <?php foreach ($css_files as $filename) { ?><link href="<?= view_cache_safe(view_fetch_themed_filename($filename, $theme_name)) ?>" rel="stylesheet">
    <?php } ?>

    <!-- TODO: move to theme style -->
    <style>
      .menu-level-3-item {
        font-size: 90%;
        padding-left: 54px !important;
      }
      .typeahead {
        overflow: hidden;
      }
    </style>
    
    <!-- script imports -->
    <?php foreach ($javascript_files as $filename) { ?><script src="<?= view_cache_safe($filename) ?>"></script>
    <?php } ?>

    <script>
            // setup default scripting after page loading.
            $( document ).ready(function() {
                // hook into jquery ajax requests to ensure csrf handling.
                $.ajaxSetup({
                    'beforeSend': function(xhr) {
                        xhr.setRequestHeader("X-CSRFToken", "<?= $csrf_token ?>" );
                    }
                });
                // propagate ajax error messages
                $( document ).ajaxError(function( event, request ) {
                    if (request.responseJSON != undefined && request.responseJSON.errorMessage != undefined) {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_DANGER,
                            title: request.responseJSON.errorTitle,
                            message:request.responseJSON.errorMessage,
                            buttons: [{
                                label: '<?= $lang->_('Close') ?>',
                                action: function(dialogItself){
                                    dialogItself.close();
                                }
                            }]
                        });
                    }
                });

                // hide empty menu items
                $('#mainmenu > div > .collapse').each(function () {
                    // cleanup empty second level menu containers
                    $(this).find("div.collapse").each(function () {
                        if ($(this).children().length == 0) {
                            $("#mainmenu").find('[href="#' + $(this).attr('id') + '"]').remove();
                            $(this).remove();
                        }
                    });

                    // cleanup empty first level menu items
                    if ($(this).children().length == 0) {
                        $("#mainmenu").find('[href="#' + $(this).attr('id') + '"]').remove();
                    }
                });
                // hide submenu items
                $('#mainmenu .list-group-item').click(function(){
                    if($(this).attr('href').substring(0,1) == '#') {
                        $('#mainmenu .list-group-item').each(function(){
                            if ($(this).attr('aria-expanded') == 'true'  && $(this).data('parent') != '#mainmenu') {
                                $("#"+$(this).attr('href').substring(1,999)).collapse('hide');
                            }
                        });
                    }
                });

                initFormHelpUI();
                initFormAdvancedUI();
                addMultiSelectClearUI();

                // Create status dialog instance
                let dialog = new BootstrapDialog({
                     title: '<?= $lang->_('System Status') ?>',
                     buttons: [{
                         label: '<?= $lang->_('Close') ?>',
                         action: function(dialogRef) {
                             dialogRef.close();
                         }
                     }],
                });

                setTimeout(function () {
                    updateSystemStatus().then((data) => {
                        let status = parseStatus(data);
                        registerStatusDelegate(dialog, status);
                    });
                }, 500);

                // Register collapsible table headers
                $('.table').on('click', 'thead', function(event) {
                    let collapse = $(event.currentTarget).next();
                    let id = collapse.attr('class');
                    if (collapse != undefined && id !== undefined && id === "collapsible") {
                        let icon = $('> tr > th > div > i', event.currentTarget);
                        if (collapse.is(':hidden')) {
                            collapse.toggle(0);
                            collapse.css('display', '');
                            icon.toggleClass("fa-angle-right fa-angle-down");
                            return;
                        }
                        icon.toggleClass("fa-angle-down fa-angle-right");
                        $('> tr > td', collapse).toggle(0);
                    }
                });

                // hook in live menu search
                $.ajax("/api/core/menu/search/", {
                    type: 'get',
                    cache: false,
                    dataType: "json",
                    data: {},
                    error : function (jqXHR, textStatus, errorThrown) {
                        console.log('menu.search : ' +errorThrown);
                    },
                    success: function (data) {
                        var menusearch_items = [];
                        $.each(data,function(idx, menu_item){
                            if (menu_item.Url != "") {
                                menusearch_items.push({id:$('<div />').html(menu_item.Url).text(), name:menu_item.breadcrumb});
                            }
                        });
                        $("#menu_search_box").typeahead({
                            source: menusearch_items,
                            matcher: function (item) {
                                var ar = this.query.trim();
                                if (ar == "") {
                                    return false;
                                }
                                ar = ar.toLowerCase().split(/\s+/);
                                if (ar.length == 0) {
                                    return false;
                                }
                                var it = this.displayText(item).toLowerCase();
                                for (var i = 0; i < ar.length; i++) {
                                    if (it.indexOf(ar[i]) == -1) {
                                        return false;
                                    }
                                }
                                return true;
                            },
                            afterSelect: function(item){
                                // (re)load page
                                if (window.location.href.split("#")[0].indexOf(item.id.split("#")[0]) > -1 ) {
                                    // same url, different hash marker
                                    window.location.href = item.id;
                                    window.location.reload();
                                } else {
                                    window.location.href = item.id;
                                }
                            }
                        });
                    }
                });

                // change search input size on focus() to fit results
                $("#menu_search_box").focus(function(){
                    $("#menu_search_box").css('width', '450px');
                    $("#system_status").hide();
                });
                $("#menu_search_box").focusout(function(){
                    $("#menu_search_box").css('width', '250px');
                    $("#system_status").show();
                });
                // enable bootstrap tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // fix menu scroll position on page load
                $(".list-group-item.active").each(function(){
                    var navbar_center = ($( window ).height() - $(".collapse.navbar-collapse").height())/2;
                    $('html,aside').scrollTop(($(this).offset().top - navbar_center));
                });
                // prevent form submits on mvc pages
                $("form").submit(function() {
                    return false;
                });

                /* overwrite clipboard paste behavior and trim before paste */
                $("input").on('paste', function(e) {
                    e.preventDefault();
                    $(this).val(e.originalEvent.clipboardData.getData("text/plain").trim())
                });

            });
        </script>

        <!-- theme JS -->
        <script src="<?= view_cache_safe(view_fetch_themed_filename('/js/theme.js', $theme_name)) ?>"></script>
  </head>
  <body>
  <header class="page-head">
    <nav class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-brand" href="/">
           <!-- <?php if (view_file_exists(join('', ['/usr/local/opnsense/www/themes/', $theme_name, '/build/images/default-logo.svg']))) { ?>
                <img class="brand-logo" src="<?= view_cache_safe(sprintf('/ui/themes/%s/build/images/default-logo.svg', $theme_name)) ?>" height="30" alt="logo"/>
            <?php } else { ?>
                <img class="brand-logo" src="<?= view_cache_safe(sprintf('/ui/themes/%s/build/images/default-logo.png', $theme_name)) ?>" height="30" alt="logo"/>
            <?php } ?>
            <?php if (view_file_exists(join('', ['/usr/local/opnsense/www/themes/', $theme_name, '/build/images/icon-logo.svg']))) { ?>
                <img class="brand-icon" src="<?= view_cache_safe(sprintf('/ui/themes/%s/build/images/icon-logo.svg', $theme_name)) ?>" height="30" alt="icon"/>
            <?php } else { ?>
                <img class="brand-icon" src="<?= view_cache_safe(sprintf('/ui/themes/%s/build/images/icon-logo.png', $theme_name)) ?>" height="30" alt="icon"/>
            <?php } ?> -->
		<!-- Logo SVG -->
		<img class="brand-logo" src="/ui/themes/dynfi/build/images/default-logo.svg" height="30" alt="logo" onerror="this.onerror=null;this.src='/ui/themes/dynfi/build/images/default-logo.png';"/>

		<!-- Icon SVG -->
		<img class="brand-icon" src="/ui/themes/dynfi/build/images/icon-logo.svg" height="30" alt="icon" onerror="this.onerror=null;this.src='/ui/themes/dynfi/build/images/icon-logo.png';"/>

          </a>
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navigation">
            <span class="sr-only"><?= $lang->_('Toggle navigation') ?></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <div class="small-screen-logout visible-xs">
            <span class="navbar-text"><?= $session_username ?>@<?= $system_hostname ?>.<?= $system_domain ?></span>
            <button class="btn btn-primary btn-logout" onclick="window.location='/index.php?logout';"><i class="fa fa-sign-out"></i></button>
          </div>
        </div>
        <button class="toggle-sidebar" data-toggle="tooltip right" title="<?= $lang->_('Toggle sidebar') ?>" style="display:none;"><i class="fa fa-bars"></i></button>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav navbar-right">
            <li id="menu_messages">
              <span class="navbar-text"><?= $session_username ?>@<?= $system_hostname ?>.<?= $system_domain ?></span>
            </li>
            <li>
              <span class="navbar-text" style="margin-left: 0">
                <i id="system_status" data-toggle="tooltip left" title="<?= $lang->_('Show system status') ?>" style="cursor:pointer" class="fa fa-circle text-muted"></i>
              </span>
            </li>
            <li>
              <button class="btn btn-primary btn-logout" onclick="window.location='/index.php?logout';"><i class="fa fa-sign-out"></i></button>
            </li>
            <li>
              <form class="navbar-form" role="search">
                <div class="input-group">
                  <div class="input-group-addon"><i class="fa fa-search"></i></div>
                  <input type="text" style="width: 250px;" class="form-control" tabindex="1" data-provide="typeahead" id="menu_search_box" autocomplete="off">
                </div>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main class="page-content col-sm-9 col-sm-push-3 col-lg-10 col-lg-push-2">
      <!-- menu system -->
      <?= $this->partial('layout_partials/base_menu_system') ?>
      <div class="row">
        <!-- page header -->
        <header class="page-content-head">
          <div class="container-fluid">
            <ul class="list-inline">
              <li><h1><?= (empty($title) ? ('') : ($title)) ?></h1></li>
              <li class="btn-group-container" id="header_buttons_container">
                <?php if ($this->length($headerButtons) > 0) { ?>
                    <?php foreach ($headerButtons as $hb) { ?>
                        <?php if ($this->length($hb['buttons']) == 1) { ?>
                            <a class="btn btn-primary" href="<?= $hb['buttons'][0]['url'] ?>"><span class="<?= $hb['iconClass'] ?>"></span> <?php if ($hb['name']) { ?><?= $lang->_($hb['name']) ?><?php } ?></a>
                        <?php } else { ?>
                            <div class="dropdown btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <span class="dropdown-text"><span class="<?= $hb['iconClass'] ?>"></span> <?= $lang->_($hb['name']) ?></span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <?php foreach ($hb['buttons'] as $button) { ?>
                                        <li><label class="dropdown-item"><a href="<?= $button['url'] ?>"><?= $lang->_($button['name']) ?></a><label></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
              </li>
              <li class="btn-group-container" id="service_status_container"></li>
            </ul>
          </div>
        </header>
        <!-- page content -->
        <section class="page-content-main">
          <div class="container-fluid">
            <div class="row">
                <section class="col-xs-12">
                    <div id="messageregion"></div>
                        <?= $this->getContent() ?>
                </section>
            </div>
          </div>
        </section>
	<footer class="page-foot">
        <div class="container-fluid about-detail-bottom">
            <div class="about-detail-bottom-left">
                <p>© 2025 BKAV Cyber Security. All rights reserved.</p>
            </div>
            <div class="about-detail-bottom-right" style="display: none">
                <svg width="87" height="81" viewBox="0 0 87 81" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 20px;">
                    <path
                        d="M22.0129 26.002V18.8946H3.4624V26.002H7.71831V41.2051H3.4624V48.3126H22.0129V41.2051H17.757V26.002H22.0129Z"
                        fill="white"></path>
                    <path
                        d="M42.4847 30.1824C41.3381 29.8488 35.4385 28.1602 34.417 27.8267C33.3955 27.4931 32.5616 27.0137 32.5616 26.4091C32.5616 25.8045 33.4997 25.1583 35.3968 25.1583C41.3798 25.1583 44.254 28.8482 44.254 28.8482C44.254 28.8482 44.5778 29.2796 44.6864 29.4344H50.4588V19.3674H44.262V21.1989C43.7887 20.872 40.2944 18.5916 34.8756 18.5916C27.7251 18.5916 23.7434 22.3023 23.7434 27.8475C23.7434 33.3928 28.7675 35.6859 30.1642 36.2696C31.561 36.8533 37.0228 38.396 39.3576 39.1256C41.6925 39.8553 42.5472 40.3764 42.5472 40.7517C42.5472 41.1269 41.5257 42.1067 38.6072 42.1067C33.5234 42.1067 30.9054 39.1088 30.4459 38.5282V38.5237C30.2998 38.3279 30.1284 38.0963 30.0702 38.0091C30.0028 37.9082 29.8465 37.6842 29.7381 37.5294H23.9657V48.2799H30.4459V46.0349C31.1999 46.5367 35.0595 48.9445 39.6912 48.9445C44.8612 48.9445 51.4488 46.693 51.4488 39.6676C51.4488 32.6423 43.6312 30.5159 42.4847 30.1824Z"
                        fill="white"></path>
                    <path
                        d="M67.9032 18.4927C59.2846 18.4927 52.2161 22.8018 52.2161 33.8018C52.2161 44.8017 59.8895 48.9219 67.9409 48.9219C75.9922 48.9219 83.5901 44.8394 83.5901 33.9153C83.5901 22.9913 75.9926 18.4927 67.9032 18.4927ZM67.9789 41.5886C65.0303 41.5886 63.1403 40.9082 63.1403 33.7641C63.1403 26.6198 64.539 25.8638 68.0921 25.8638C70.2467 25.8638 72.855 26.0149 72.855 33.5373C72.855 41.0596 70.9275 41.5886 67.9789 41.5886Z"
                        fill="white"></path>
                    <path
                        d="M15.8231 15.6503C17.7605 12.7895 20.143 10.2535 22.8743 8.14747C23.9787 8.964 25.1526 9.70465 26.3867 10.3647C24.3191 12.6696 22.4665 15.223 22.1597 15.6503H23.4003C24.145 14.6467 25.6853 12.6277 27.3095 10.8377C29.0848 11.7104 30.9715 12.4242 32.9401 12.9679C32.4161 14.0796 32.02 15.0518 31.7875 15.6503H32.8654C33.1131 15.0339 33.476 14.1742 33.9288 13.2242C36.44 13.8378 39.0714 14.1817 41.7633 14.2341V15.6505H42.764V14.2389C45.4362 14.2194 48.053 13.9149 50.5555 13.3427C50.9821 14.2451 51.3255 15.0598 51.5628 15.6503H52.6407C52.4172 15.0754 52.0433 14.1565 51.5495 13.0993C53.5533 12.578 55.476 11.8829 57.2868 11.0238C58.8488 12.7663 60.3091 14.6815 61.0281 15.6503H62.2685C61.9712 15.2362 60.2145 12.8142 58.2184 10.5629C59.5908 9.85358 60.892 9.04655 62.1083 8.14747C64.8395 10.2535 67.2222 12.7895 69.1595 15.6503H70.3631C64.4446 6.51592 54.1623 0.459351 42.4911 0.459351C30.8199 0.459351 20.5376 6.51592 14.6191 15.6503H15.8231ZM33.3866 12.0528C31.514 11.547 29.7174 10.8839 28.0236 10.0727C28.2538 9.83356 28.4837 9.60191 28.7111 9.3826C31.0304 7.146 34.8452 4.69008 35.6814 4.1614C36.36 4.53414 37.1005 4.84935 37.8881 5.09951C37.2764 5.90169 36.0753 7.53008 34.7673 9.59558C34.2581 10.3994 33.7943 11.2443 33.3866 12.0528ZM41.7633 13.2331C39.229 13.1824 36.7511 12.8669 34.3826 12.3052C34.753 11.581 35.1662 10.8359 35.6127 10.1311C37.1197 7.75172 38.4792 5.96523 38.9322 5.38403C39.8359 5.59633 40.7857 5.73058 41.7633 5.77445V13.2331ZM42.764 13.2381V5.78578C43.7284 5.76944 44.672 5.66738 45.5751 5.48576C46.0928 6.15536 47.3883 7.87713 48.8157 10.1311C49.2853 10.8727 49.7184 11.6589 50.1035 12.4186C47.7433 12.9406 45.2797 13.2194 42.764 13.2381ZM51.1067 12.1816C50.6836 11.3344 50.1971 10.4421 49.6609 9.59558C48.4204 7.63665 47.2757 6.07064 46.6391 5.22976C47.4768 4.99444 48.2677 4.68858 48.9938 4.31851C50.1677 5.07099 53.5773 7.31911 55.7171 9.3826C56.0012 9.65661 56.289 9.9493 56.5765 10.2528C54.8474 11.0512 53.0146 11.6974 51.1067 12.1816ZM61.2704 7.5219C60.0965 8.36928 58.8422 9.12844 57.5215 9.79587C57.1524 9.39995 56.7802 9.01754 56.4118 8.66231C54.4103 6.73223 51.4302 4.70742 49.9701 3.75664C50.4661 3.4351 50.9197 3.07954 51.3241 2.69246C54.9383 3.72512 58.2936 5.37635 61.2704 7.5219ZM42.4914 1.46C45.1338 1.46 47.702 1.78237 50.1611 2.38576C48.2949 3.90157 45.4986 4.79081 42.4914 4.79081C39.4707 4.79081 36.682 3.90441 34.8183 2.38659C37.2783 1.7827 39.8476 1.46 42.4914 1.46ZM33.6583 2.69246C33.9875 3.00799 34.3479 3.30318 34.7383 3.57536C33.4154 4.42658 30.1572 6.59814 28.0166 8.66214C27.7084 8.95933 27.3977 9.27587 27.0882 9.60308C25.9043 8.98318 24.7752 8.2889 23.7127 7.5219C26.6889 5.37635 30.0441 3.72529 33.6583 2.69246Z"
                        fill="white"></path>
                    <path
                        d="M69.1613 51.6457C67.2651 54.4464 64.9419 56.9353 62.283 59.0146C61.0167 58.0624 59.6575 57.2112 58.2201 56.4677C59.9517 54.5144 61.5032 52.4327 62.0776 51.6459H60.8314C60.0633 52.6716 58.7186 54.4115 57.2883 56.007C55.4775 55.1477 53.5549 54.452 51.5508 53.9309C51.9745 53.0235 52.3093 52.2193 52.5371 51.6459H51.456C51.2269 52.2049 50.9222 52.9142 50.5561 53.6886C48.0552 53.1172 45.4387 52.8128 42.7643 52.7935V51.6459H41.7636V52.798C39.0696 52.8502 36.4384 53.1937 33.9286 53.8065C33.5377 52.9864 33.2136 52.2331 32.973 51.6459H31.8918C32.1304 52.247 32.4866 53.1015 32.9396 54.0625C30.9705 54.6063 29.0831 55.3191 27.308 56.1923C25.8163 54.5481 24.3959 52.7114 23.5976 51.6457H22.3514C22.9448 52.4588 24.5837 54.6572 26.3869 56.6668C25.0882 57.3623 23.8551 58.1459 22.7002 59.0145C20.0411 56.9351 17.7179 54.4462 15.8219 51.6456H14.6201C20.5388 60.78 30.8207 66.8365 42.4918 66.8365C54.1626 66.8365 64.4448 60.7801 70.3634 51.6456L69.1613 51.6457ZM51.1073 54.8491C53.0159 55.3331 54.8487 55.9792 56.578 56.7779C56.29 57.0817 56.002 57.3749 55.7175 57.6494C53.5843 59.7063 50.1856 61.9492 49.0028 62.7077C48.2767 62.3361 47.4848 62.0303 46.6444 61.7949C47.2822 60.9526 48.4239 59.3896 49.6611 57.4361C50.1976 56.5894 50.684 55.6967 51.1073 54.8491ZM42.7641 53.794C45.2819 53.8126 47.7455 54.0906 50.1042 54.612C49.7189 55.3721 49.2858 56.1585 48.8157 56.9008C47.3903 59.1516 46.0968 60.8715 45.5776 61.5431C44.677 61.3637 43.7334 61.2624 42.7639 61.2464V53.794H42.7641ZM41.7635 53.799V61.2568C40.7822 61.3003 39.8314 61.4329 38.9288 61.6437C38.4727 61.0581 37.1175 59.2767 35.6128 56.9008C35.166 56.1955 34.7524 55.4499 34.3819 54.7251C36.7491 54.164 39.227 53.8495 41.7635 53.799ZM33.3862 54.9782C33.7942 55.7872 34.2583 56.6321 34.7674 57.4363C36.0745 59.5001 37.2747 61.1275 37.887 61.9304C37.0981 62.1804 36.3585 62.4959 35.6802 62.8696C34.8408 62.3386 31.0294 59.8845 28.7114 57.6494C28.4837 57.43 28.2537 57.198 28.0233 56.9587C29.7167 56.1471 31.5132 55.4839 33.3862 54.9782ZM23.5315 59.6419C24.6468 58.822 25.8359 58.0829 27.0868 57.4276C27.3967 57.7552 27.7081 58.0722 28.0166 58.3697C30.1566 60.4332 33.4139 62.6043 34.7373 63.4558C34.2658 63.7859 33.836 64.1488 33.4541 64.5427C29.8448 63.485 26.4968 61.8106 23.5315 59.6419ZM42.4916 65.8361C39.7627 65.8361 37.1123 65.4933 34.5803 64.8511C36.4292 63.2264 39.3651 62.2412 42.4916 62.2412C45.6143 62.2412 48.553 63.227 50.402 64.8512C47.8702 65.4935 45.2202 65.8361 42.4916 65.8361ZM51.5284 64.5427C51.0745 64.0749 50.5556 63.6483 49.9768 63.2709C51.4394 62.3185 54.4135 60.2968 56.4119 58.3696C56.7805 58.0142 57.1527 57.6316 57.5221 57.2353C58.9102 57.9375 60.2257 58.7406 61.4515 59.6417C58.4861 61.8108 55.1379 63.4852 51.5284 64.5427Z"
                        fill="white"></path>
                    <path
                        d="M0.127686 72.3901C0.250273 71.2731 0.6589 70.3742 1.35356 69.6931C2.06185 68.9985 3.02212 68.6511 4.23438 68.6511C4.8337 68.6511 5.36491 68.7533 5.82802 68.9576C6.29113 69.1483 6.67933 69.4139 6.99261 69.7544C7.30589 70.0813 7.54425 70.4559 7.7077 70.8781C7.87115 71.3004 7.95288 71.7363 7.95288 72.1857C7.95288 72.5807 7.89839 72.9349 7.78943 73.2482C7.69408 73.5614 7.55106 73.8407 7.36037 74.0859C7.1833 74.331 6.97218 74.5626 6.727 74.7805C6.48182 74.9848 6.21622 75.1823 5.93018 75.373C5.39896 75.7544 4.84732 76.1222 4.27524 76.4763C3.71679 76.8305 3.17876 77.2323 2.66117 77.6818C2.47048 77.8452 2.31384 78.0223 2.19125 78.213C2.06866 78.39 1.9665 78.6148 1.88478 78.8872H7.62598V80.3583H0.250273V79.255C0.250273 78.7919 0.406913 78.3424 0.720194 77.9065C1.03347 77.457 1.42167 77.028 1.88478 76.6193C2.36151 76.1971 2.8791 75.7953 3.43756 75.4139C4.00963 75.0189 4.54766 74.6375 5.05163 74.2697C5.43302 73.9973 5.73268 73.7045 5.95061 73.3912C6.18216 73.0779 6.29794 72.6761 6.29794 72.1857C6.29794 71.4911 6.10725 70.9531 5.72587 70.5717C5.34448 70.1767 4.84732 69.9792 4.23438 69.9792C3.48523 69.9792 2.89272 70.1971 2.45686 70.633C2.02099 71.0552 1.80305 71.6409 1.80305 72.3901H0.127686Z"
                        fill="white"></path>
                    <path
                        d="M10.9185 80.3583C11.1772 78.4105 11.7017 76.6057 12.4917 74.944C13.2953 73.2686 14.3577 71.675 15.6789 70.163V70.1426H9.20222V68.8146H17.2317V70.1426C16.5779 70.9326 15.9786 71.7635 15.4338 72.6352C14.9026 73.4933 14.4326 74.3651 14.024 75.2504C13.629 76.1222 13.3021 76.9939 13.0433 77.8656C12.7981 78.7374 12.6347 79.5683 12.553 80.3583H10.9185Z"
                        fill="white"></path>
                    <path
                        d="M22.4447 68.6511C25.1281 68.6511 26.4697 70.5989 26.4697 74.4945C26.4697 76.4831 26.1292 77.9882 25.4481 79.0098C24.7807 80.0177 23.7796 80.5217 22.4447 80.5217C21.1099 80.5217 20.102 80.0177 19.4209 79.0098C18.7535 77.9882 18.4198 76.4831 18.4198 74.4945C18.4198 72.6965 18.7535 71.2731 19.4209 70.2243C20.102 69.1755 21.1099 68.6511 22.4447 68.6511ZM22.4447 69.9792C21.6139 69.9792 21.0077 70.3333 20.6264 71.0416C20.2586 71.7499 20.0747 72.9008 20.0747 74.4945C20.0747 76.1017 20.2586 77.2868 20.6264 78.0495C21.0077 78.8123 21.6139 79.1937 22.4447 79.1937C23.2756 79.1937 23.8749 78.8123 24.2427 78.0495C24.6241 77.2868 24.8148 76.1017 24.8148 74.4945C24.8148 72.9008 24.6241 71.7499 24.2427 71.0416C23.8749 70.3333 23.2756 69.9792 22.4447 69.9792Z"
                        fill="white"></path>
                    <path
                        d="M31.6827 68.6511C34.366 68.6511 35.7077 70.5989 35.7077 74.4945C35.7077 76.4831 35.3672 77.9882 34.6861 79.0098C34.0187 80.0177 33.0176 80.5217 31.6827 80.5217C30.3479 80.5217 29.3399 80.0177 28.6589 79.0098C27.9915 77.9882 27.6578 76.4831 27.6578 74.4945C27.6578 72.6965 27.9915 71.2731 28.6589 70.2243C29.3399 69.1755 30.3479 68.6511 31.6827 68.6511ZM31.6827 69.9792C30.8519 69.9792 30.2457 70.3333 29.8643 71.0416C29.4966 71.7499 29.3127 72.9008 29.3127 74.4945C29.3127 76.1017 29.4966 77.2868 29.8643 78.0495C30.2457 78.8123 30.8519 79.1937 31.6827 79.1937C32.5136 79.1937 33.1129 78.8123 33.4807 78.0495C33.8621 77.2868 34.0528 76.1017 34.0528 74.4945C34.0528 72.9008 33.8621 71.7499 33.4807 71.0416C33.1129 70.3333 32.5136 69.9792 31.6827 69.9792Z"
                        fill="white"></path>
                    <path
                        d="M40.8594 72.0019H38.1829V70.8577H39.0206C39.3203 70.8577 39.5995 70.8032 39.8583 70.6943C40.1307 70.5853 40.3623 70.4355 40.5529 70.2448C40.7436 70.0541 40.8935 69.8225 41.0024 69.5501C41.125 69.2777 41.1863 68.978 41.1863 68.6511H42.3509V80.3583H40.8594V72.0019Z"
                        fill="white"></path>
                    <path
                        d="M48.7489 70.8373V72.8191H46.9714V70.8373H48.7489ZM48.7489 78.356V80.3583H46.9714V78.356H48.7489Z"
                        fill="white"></path>
                    <path
                        d="M51.3855 72.3901C51.5081 71.2731 51.9167 70.3742 52.6114 69.6931C53.3197 68.9985 54.28 68.6511 55.4922 68.6511C56.0915 68.6511 56.6228 68.7533 57.0859 68.9576C57.549 69.1483 57.9372 69.4139 58.2505 69.7544C58.5637 70.0813 58.8021 70.4559 58.9655 70.8781C59.129 71.3004 59.2107 71.7363 59.2107 72.1857C59.2107 72.5807 59.1562 72.9349 59.0473 73.2482C58.9519 73.5614 58.8089 73.8407 58.6182 74.0859C58.4411 74.331 58.23 74.5626 57.9848 74.7805C57.7397 74.9848 57.4741 75.1823 57.188 75.373C56.6568 75.7544 56.1052 76.1222 55.5331 76.4763C54.9746 76.8305 54.4366 77.2323 53.919 77.6818C53.7283 77.8452 53.5717 78.0223 53.4491 78.213C53.3265 78.39 53.2243 78.6148 53.1426 78.8872H58.8838V80.3583H51.5081V79.255C51.5081 78.7919 51.6648 78.3424 51.978 77.9065C52.2913 77.457 52.6795 77.028 53.1426 76.6193C53.6194 76.1971 54.1369 75.7953 54.6954 75.4139C55.2675 75.0189 55.8055 74.6375 56.3095 74.2697C56.6909 73.9973 56.9905 73.7045 57.2085 73.3912C57.44 73.0779 57.5558 72.6761 57.5558 72.1857C57.5558 71.4911 57.3651 70.9531 56.9837 70.5717C56.6023 70.1767 56.1052 69.9792 55.4922 69.9792C54.7431 69.9792 54.1506 70.1971 53.7147 70.633C53.2788 71.0552 53.0609 71.6409 53.0609 72.3901H51.3855Z"
                        fill="white"></path>
                    <path
                        d="M64.4646 68.6511C67.1479 68.6511 68.4896 70.5989 68.4896 74.4945C68.4896 76.4831 68.149 77.9882 67.468 79.0098C66.8006 80.0177 65.7994 80.5217 64.4646 80.5217C63.1298 80.5217 62.1218 80.0177 61.4408 79.0098C60.7733 77.9882 60.4396 76.4831 60.4396 74.4945C60.4396 72.6965 60.7733 71.2731 61.4408 70.2243C62.1218 69.1755 63.1298 68.6511 64.4646 68.6511ZM64.4646 69.9792C63.6337 69.9792 63.0276 70.3333 62.6462 71.0416C62.2785 71.7499 62.0946 72.9008 62.0946 74.4945C62.0946 76.1017 62.2785 77.2868 62.6462 78.0495C63.0276 78.8123 63.6337 79.1937 64.4646 79.1937C65.2955 79.1937 65.8948 78.8123 66.2626 78.0495C66.6439 77.2868 66.8346 76.1017 66.8346 74.4945C66.8346 72.9008 66.6439 71.7499 66.2626 71.0416C65.8948 70.3333 65.2955 69.9792 64.4646 69.9792Z"
                        fill="white"></path>
                    <path
                        d="M69.8615 72.3901C69.9841 71.2731 70.3927 70.3742 71.0874 69.6931C71.7957 68.9985 72.7559 68.6511 73.9682 68.6511C74.5675 68.6511 75.0987 68.7533 75.5618 68.9576C76.0249 69.1483 76.4131 69.4139 76.7264 69.7544C77.0397 70.0813 77.2781 70.4559 77.4415 70.8781C77.605 71.3004 77.6867 71.7363 77.6867 72.1857C77.6867 72.5807 77.6322 72.9349 77.5232 73.2482C77.4279 73.5614 77.2849 73.8407 77.0942 74.0859C76.9171 74.331 76.706 74.5626 76.4608 74.7805C76.2156 74.9848 75.95 75.1823 75.664 75.373C75.1328 75.7544 74.5811 76.1222 74.0091 76.4763C73.4506 76.8305 72.9126 77.2323 72.395 77.6818C72.2043 77.8452 72.0477 78.0223 71.9251 78.213C71.8025 78.39 71.7003 78.6148 71.6186 78.8872H77.3598V80.3583H69.9841V79.255C69.9841 78.7919 70.1407 78.3424 70.454 77.9065C70.7673 77.457 71.1555 77.028 71.6186 76.6193C72.0953 76.1971 72.6129 75.7953 73.1714 75.4139C73.7434 75.0189 74.2815 74.6375 74.7854 74.2697C75.1668 73.9973 75.4665 73.7045 75.6844 73.3912C75.916 73.0779 76.0318 72.6761 76.0318 72.1857C76.0318 71.4911 75.8411 70.9531 75.4597 70.5717C75.0783 70.1767 74.5811 69.9792 73.9682 69.9792C73.219 69.9792 72.6265 70.1971 72.1907 70.633C71.7548 71.0552 71.5369 71.6409 71.5369 72.3901H69.8615Z"
                        fill="white"></path>
                    <path
                        d="M79.0995 72.3901C79.2221 71.2731 79.6307 70.3742 80.3254 69.6931C81.0336 68.9985 81.9939 68.6511 83.2062 68.6511C83.8055 68.6511 84.3367 68.7533 84.7998 68.9576C85.2629 69.1483 85.6511 69.4139 85.9644 69.7544C86.2777 70.0813 86.5161 70.4559 86.6795 70.8781C86.8429 71.3004 86.9247 71.7363 86.9247 72.1857C86.9247 72.5807 86.8702 72.9349 86.7612 73.2482C86.6659 73.5614 86.5229 73.8407 86.3322 74.0859C86.1551 74.331 85.944 74.5626 85.6988 74.7805C85.4536 74.9848 85.188 75.1823 84.902 75.373C84.3708 75.7544 83.8191 76.1222 83.247 76.4763C82.6886 76.8305 82.1506 77.2323 81.633 77.6818C81.4423 77.8452 81.2856 78.0223 81.163 78.213C81.0405 78.39 80.9383 78.6148 80.8566 78.8872H86.5978V80.3583H79.2221V79.255C79.2221 78.7919 79.3787 78.3424 79.692 77.9065C80.0053 77.457 80.3935 77.028 80.8566 76.6193C81.3333 76.1971 81.8509 75.7953 82.4094 75.4139C82.9814 75.0189 83.5195 74.6375 84.0234 74.2697C84.4048 73.9973 84.7045 73.7045 84.9224 73.3912C85.154 73.0779 85.2697 72.6761 85.2697 72.1857C85.2697 71.4911 85.079 70.9531 84.6977 70.5717C84.3163 70.1767 83.8191 69.9792 83.2062 69.9792C82.457 69.9792 81.8645 70.1971 81.4287 70.633C80.9928 71.0552 80.7748 71.6409 80.7748 72.3901H79.0995Z"
                        fill="white"></path>
                </svg>
            </div>
        </div>
    </footer>
      </div>
    </main>

    <!-- dialog "wait for (service) action" -->
    <div class="modal fade" id="OPNsenseStdWaitDialog" tabindex="-1" data-backdrop="static" data-keyboard="false">
      <div class="modal-backdrop fade in"></div>
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <p><strong><?= $lang->_('Please wait...') ?></strong></p>
            <div class="progress">
               <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div>
             </div>
          </div>
        </div>
      </div>
    </div>

    <script>
    /* hook translations  when all JS modules are loaded*/
    $.extend(jQuery.fn.bootgrid.prototype.constructor.Constructor.defaults.labels, {
        all: "<?= $lang->_('All') ?>",
        infos: "<?= sprintf($lang->_('Showing %s to %s of %s entries'), '{{ctx.start}}', '{{ctx.end}}', '{{ctx.total}}') ?>",
        loading: "<?= $lang->_('Loading...') ?>",
        noResults: "<?= $lang->_('No results found!') ?>",
        refresh: "<?= $lang->_('Refresh') ?>",
        search: "<?= $lang->_('Search') ?>"
    });
    $.extend(jQuery.fn.selectpicker.Constructor.DEFAULTS, {
        noneSelectedText: "<?= $lang->_('Nothing selected') ?>",
        noneResultsText: "<?= $lang->_('No results matched {0}') ?>",
        selectAllText: "<?= $lang->_('Select All') ?>",
        deselectAllText: "<?= $lang->_('Deselect All') ?>"
    });
    $.extend(jQuery.fn.UIBootgrid.defaults, {
        removeWarningText: "<?= $lang->_('Remove selected item(s)?') ?>",
        editText: "<?= $lang->_('Edit') ?>",
        cloneText: "<?= $lang->_('Clone') ?>",
        deleteText: "<?= $lang->_('Delete') ?>",
        addText: "<?= $lang->_('Add') ?>",
        infoText: "<?= $lang->_('Info') ?>",
        enableText: "<?= $lang->_('Enable') ?>",
        disableText: "<?= $lang->_('Disable') ?>",
        deleteSelectedText: "<?= $lang->_('Delete selected') ?>"
    });
    $.extend(stdDialogRemoveItem.defaults, {
        title: "<?= $lang->_('Remove') ?>",
        accept: "<?= $lang->_('Yes') ?>",
        decline: "<?= $lang->_('Cancel') ?>"
    });
    </script>

  </body>
</html>
