 <?php
     require_once dirname(__DIR__, 2) . "/short.php"
     ?>
 <!DOCTYPE html>
 <html lang="en">


 <!-- Mirrored from <?php echo $urlh; ?>/ by HTTrack Website Copier/3.x [XR&CO'2014], Fri, 21 Feb 2025 01:10:31 GMT -->
 <!-- Added by HTTrack -->
 <meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->

 <head>


     <meta name="viewport" content="width=device-width, initial-scale=1" />
     <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <link rel="icon" href="<?php echo $url; ?>/<?php echo $favicon_path; ?>" />


     <meta name="description"
         content="<?php echo $name; ?> is to provide the highest-quality advanced imaging in a patient-centered and compassionate environment">
     <meta property="og:description"
         content="<?php echo $name; ?> is to provide the highest-quality advanced imaging in a patient-centered and compassionate environment" />





     <meta http-equiv="X-UA-Compatible" content="IE=edge">



     <!-- Document title -->
     <title>


         <?php echo $name; ?> | Highest-quality advanced imaging in a compassionate environment


     </title>
     <!-- Stylesheets & Fonts -->
     <link href="<?php echo $url; ?>/static/css/plugins.css" rel="stylesheet">
     <link href="<?php echo $url; ?>/static/css/nyimg_style.css" rel="stylesheet">
    <?php
    $themeCssExists = $theme_css_path !== '' && file_exists(dirname(__DIR__, 2) . '/' . $theme_css_path);
    $themeExtraCssVars = '';
    if (isset($theme_extra_colors) && is_array($theme_extra_colors)) {
        foreach ($theme_extra_colors as $extraKey => $extraValue) {
            $safeKey = preg_replace('/[^a-z0-9_-]/i', '', (string) $extraKey);
            if ($safeKey === '') {
                continue;
            }

            $themeExtraCssVars .= '--theme-' . str_replace('_', '-', strtolower($safeKey)) . ': ' . htmlspecialchars((string) $extraValue, ENT_QUOTES, 'UTF-8') . ";\n";
        }
    }
    if ($themeCssExists) {
        ?>
        <link href="<?php echo $url; ?>/<?php echo htmlspecialchars($theme_css_path, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php } ?>
     <style>
         :root {
             --brand-primary: <?php echo htmlspecialchars($theme_primary, ENT_QUOTES, 'UTF-8'); ?>;
             --brand-accent: <?php echo htmlspecialchars($theme_accent, ENT_QUOTES, 'UTF-8'); ?>;
             --brand-surface: <?php echo htmlspecialchars($theme_surface, ENT_QUOTES, 'UTF-8'); ?>;
             --brand-text: <?php echo htmlspecialchars($theme_text, ENT_QUOTES, 'UTF-8'); ?>;
             --brand-muted: <?php echo htmlspecialchars($theme_muted, ENT_QUOTES, 'UTF-8'); ?>;
             <?php echo $themeExtraCssVars; ?>
         }

         body {
             color: var(--brand-text);
             background-color: var(--brand-surface);
         }

         a {
             color: var(--brand-accent);
         }
     </style>



     <meta property="og:title" content=" 
        
            
                <?php echo $name; ?> | Highest-quality advanced imaging in a compassionate environment
            
        
    " />
     <meta property="og:type" content="website" />

     <style>
         @media only screen and (min-device-width: 375px) and (max-device-width: 812px) and (-webkit-min-device-pixel-ratio: 3) and (orientation: portrait) {
             .inspiro-slider .slide {
                 /*background-size: contain; */
                 height: 360px;

             }

             .topsecmob {
                 top: 10px;
             }

             .hide-mbl {
                 visibility: hidden;
             }

             .flickity-viewport {
                 height: 360px !important;
                 touch-action: pan-y;
             }

             #slider {
                 height: 360px !important;
             }

         }

         @media only screen and (min-device-width: 375px) and (max-device-width: 812px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: portrait) {
             .inspiro-slider .slide {
                 /*background-size: contain;*/
                 height: 360px;

             }

             .topsecmob {
                 top: 10px;
             }

             .hide-mbl {
                 visibility: hidden;
             }

             .flickity-viewport {
                 height: 360px !important;
                 touch-action: pan-y;
             }

             #slider {
                 height: 360px !important;
             }

         }

         @media only screen and (min-device-width: 300px) and (max-device-width: 812px) and (-webkit-min-device-pixel-ratio: 2) and (orientation: portrait) {
             .inspiro-slider .slide {
                 /*background-size: contain;*/
                 height: 360px;
             }

             .topsecmob {
                 top: 10px;
             }

             .hide-mbl {
                 visibility: hidden;
             }

             .flickity-viewport {
                 height: 360px !important;
                 touch-action: pan-y;
             }

             #slider {
                 height: 360px !important;
             }

         }

         @media (max-width: 991px) {
             .flickity-viewport {
                 height: 360px !important;
                 touch-action: pan-y;
             }

             #slider {
                 height: 360px !important;
             }

             #mainMenu .mobile-portal-nav {
                 display: list-item !important;
             }

             #mainMenu .mobile-portal-nav > a {
                 font-weight: 700;
                 color: #fff !important;
                 background-color: var(--brand-primary);
                 border-radius: 8px;
                 margin-top: 8px;
             }

             #mainMenu .mobile-portal-nav > a:hover {
                 background-color: var(--brand-accent);
             }

         }

         #mainMenu .mobile-portal-nav {
             display: none;
         }
     </style>



 </head>

 <body class="<?php echo htmlspecialchars($theme_body_class, ENT_QUOTES, 'UTF-8'); ?>">


     <div class="body-inner">

         <div id="topbar" class="d-xl-block d-lg-block">
             <div class="container">
                 <div class="row">
                     <div class="col-md-12">
                         <ul class="top-menu-box top-menu" style="float:right;">

                             <li><a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a></li>

                         </ul>
                     </div>
                     <div class="d-none" style="display:none;">
                         <div class="social-icons social-icons-colored-hover">
                             <ul>

                             </ul>
                         </div>
                     </div>
                 </div>
             </div>
         </div>


         <div class="container">

             <header id="header" data-transparent="true" data-fullwidth="true" class="light submenu-light"
                 style="background-color: #FFF;">
                 <div class="header-inner">
                     <div class="container">

                         <div id="logo">
                             <a href="<?php echo $url; ?>/index.php"><span class=""><img
                                         src="<?php echo $url; ?>/<?php echo $logo_path; ?>"
                                         style="max-width:250px;" alt="<?php echo $name; ?>  Logo" /></span></a>
                         </div>




                         <div class="header-extras">
                             <ul>

                                 <li class="d-none d-xl-block d-lg-block">
                                     <a href="<?php echo $url; ?>/portal/index.php" class="btn btn-rounded btn-sm"
                                         style="background-color: var(--brand-primary);border-color: var(--brand-primary); color: #FFF;">Patient
                                         Portal</a>
                                 </li>

                             </ul>
                         </div>





                         <div id="mainMenu-trigger">
                             <button class="lines-button x"> <span class="lines"></span></button>
                         </div>



                         <div id="mainMenu" class="light">
                             <div class="container">
                                 <nav>
                                     <ul>
                                         <li class="dropdown">
                                             <a href="#">About our clinic</a>
                                             <ul class="dropdown-menu">

                                                 <li class=""><a href="<?php echo $url; ?>/about-us/index.php">About
                                                         Us</a></li>
                                                 <li><a href="<?php echo $url; ?>/people.php">Our Team</a></li>

                                             </ul>
                                         </li>



                                         <li>
                                             <a href="<?php echo $url; ?>/our-equipment/index.php">Services</a>
                                             <ul class="dropdown-menu">
                                                 <li class=""><a href="<?php echo $url; ?>/dexa-scan/index.php">Bone
                                                         Density</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/computed-tomography-ct/index.php">Computed
                                                         Tomography (CT)</a></li>

                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/main-interventional-radiology-oncology/index.php">Interventional
                                                         Radiology &amp; Oncology</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/lung-screening/index.php">Lung
                                                         Screening</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/mammo/index.php">Mammography</a>
                                                 </li>
                                                 <li class=""><a href="<?php echo $url; ?>/mri/index.php">MRI</a>
                                                 </li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/fdg-pet-ct-scan/index.php">PET/CT
                                                         Scan</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/radionuclide-therapies-2/index.php">Radionuclide
                                                         Therapies</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/transthoracic-echocardiogram-tte/index.php">Transthoracic
                                                         Echocardiogram (TTE)</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/ultrasound-2/index.php">Ultrasound</a>
                                                 </li>

                                             </ul>
                                         </li>




                                         <li class="dropdown">
                                             <a href="#">Policies</a>
                                             <ul class="dropdown-menu">

                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/hipaa-notice-of-privacy-practices.php">HIPAA
                                                         and
                                                         Compliance</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/ada-statement/index.php">ADA
                                                         Statement</a></li>
                                                 <li class=""><a
                                                         href="<?php echo $url; ?>/covid-policy/index.php">COVID-19
                                                         POLICY</a></li>

                                             </ul>
                                         </li>
                                         <!--<li><a href="<?php echo $url; ?>/payment/index.php">Payment</a></li>-->
                                         <li><a href="<?php echo $url; ?>/contact.php">Contact Us &nbsp;</a></li>
                                         <li class="mobile-portal-nav"><a href="<?php echo $url; ?>/portal/index.php">Patient Portal</a></li>

                                     </ul>

                                 </nav>
                             </div>
                         </div>

                     </div>
                 </div>
             </header>
         </div>
         <!-- end: Header -->