<?php
include "../header.php"
?>

<!--<body>







    
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
    <a href="../index.php"><span class=""><img src="../static/images/NY_Imaging_Specialists.png"
                style="max-width:250px;" alt="<?php echo $name; ?>  Logo" /></span></a>
</div>




<div class="header-extras">
    <ul>

        <li class="d-none d-xl-block d-lg-block">
            <a href="<?php echo $url; ?>/contact.php" class="btn btn-rounded btn-sm"
                style="background-color: rgb(76,66,135);border-color: rgb(76,66,135); color: #FFF;">Make
                Appointment</a>
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
                <li><a href="../locations.php">Locations</a></li>
                <li><a href="../people.php">Our Team</a></li>


                <li>
                    <a href="../our-equipment/index.php">Services</a>
                    <ul class="dropdown-menu">
                        <li class=""><a href="../dexa-scan/index.php">Bone Density</a></li>
                        <li class=""><a href="../computed-tomography-ct/index.php">Computed
                                Tomography (CT)</a></li>

                        <li class=""><a
                                href="../main-interventional-radiology-oncology/index.php">Interventional
                                Radiology &amp; Oncology</a></li>
                        <li class=""><a href="../lung-screening/index.php">Lung Screening</a>
                        </li>
                        <li class=""><a href="../mammo/index.php">Mammography</a></li>
                        <li class=""><a href="../mri/index.php">MRI</a></li>
                        <li class=""><a href="../fdg-pet-ct-scan/index.php">PET/CT Scan</a></li>
                        <li class=""><a
                                href="../radionuclide-therapies-2/index.php">Radionuclide
                                Therapies</a></li>
                        <li class=""><a
                                href="../transthoracic-echocardiogram-tte/index.php">Transthoracic
                                Echocardiogram (TTE)</a></li>
                        <li class=""><a href="../ultrasound-2/index.php">Ultrasound</a></li>

                    </ul>
                </li>




                <li class="dropdown">
                    <a href="#">About</a>
                    <ul class="dropdown-menu">
                        <li class=""><a
                                href="../insurance-billing-information/index.php">Accepted
                                Insurance</a></li>
                        <li class=""><a href="../about-us/index.php">About Us</a></li>
                        <li class=""><a href="../hipaa-notice-of-privacy-practices.php">HIPAA
                                and Compliance</a></li>
                        <li class=""><a href="../ada-statement/index.php">ADA Statement</a></li>
                        <li class=""><a href="../covid-policy/index.php">COVID-19 POLICY</a>
                        </li>
                        <li class=""><a href="../nassau-county/index.php">Nassau County</a></li>
                        <li class=""><a href="../news.php">News</a></li>
                    </ul>
                </li>
                <li><a href="../payment/index.php">Payment</a></li>
                <li class="dropdown">
                    <a href="#">Portals</a>
                    <ul class="dropdown-menu">

                        <li class=""><a
                                href="https://accounts.flatiron.com/account/login/?ReturnUrl=%2Fconnect%2Fauthorize%2Fcallback%3Fclient_id%3Dfh.patient_portal%26nonce%3D17h7jA8gPTj9SJMK%26redirect_uri%3Dhttps%253A%252F%252Fcarespaceportal.com%252Fcb%26response_type%3Dcode%26scope%3Dopenid%2520profile%26state%3DxcuQzpriokHxgTi1">Patients</a>
                        </li>

                        <li class="dropdown-submenu"><span class="dropdown-arrow"></span><a
                                href="#">Physicians</a>
                            <ul class="dropdown-menu">
                                <li class=""><a href="../infinitt/index.php">INFINITT</a></li>

                                <li><a
                                        href="https://scriptsender.nycancer.com/">ScriptSender</a>
                                </li>
                            </ul>
                        </li>



                    </ul>
                </li>

            </ul>

        </nav>
    </div>
</div>

</div>
</div>
</header>
</div>
<!-- end: Header -->


<section id="page-content">
    <div class="container">
        <div class="row">
            <!-- content -->
            <div class="content col-lg-12">
                <!-- Blog -->
                <div id="blog" class="single-post">
                    <!-- Post single item-->
                    <div class="post-item">
                        <div class="post-item-wrap">
                            <div class="post-image">

                                <a href="#">
                                    <img alt="" src="../media/blog/prostatemri-01.png">
                                </a>

                            </div>
                            <div class="post-item-description">
                                <h1><?php echo $name; ?> Earns ACR Prostate Cancer MRI Center of Excellence Designation
                                </h1>
                                <div class="post-meta">
                                    <span class="post-meta-date"><i class="fa fa-calendar-o"></i>Aug. 10,
                                        2023</span>
                                </div>
                                <p dir="ltr"><span><?php echo $name; ?> has been named a Prostate Cancer MRI
                                        Center of Excellence by the American College of Radiology.</span></p>
                                <p dir="ltr"><span>This recognition is granted to imaging facilities with full
                                        accreditation in all body MR-imaging programs, including specialized
                                        prostate imaging</span><span>.</span><span>&nbsp; The ACR&rsquo;s gold
                                        seal recognizes centers that have met the high-quality MR prostate
                                        imaging standards for personnel, equipment, quality assurance and
                                        quality control procedure requirements for patient safety. The
                                        accreditation sets <?php echo $name; ?> apart as a leader in providing
                                        world-class prostate cancer care.</span></p>
                                <p dir="ltr"><span>&ldquo;Being named a Prostate Cancer MRI Center of Excellence
                                        reflects the knowledge and skill of our MR imaging technologists and
                                        radiologists. It is a testament to our robust prostate MRI program and
                                        highlights the dedication we have towards providing world-class care
                                        ,&rdquo; said Jeff Vacirca, MD, CEO.</span></p>
                                <p dir="ltr"><span>Prostate MRI has changed the paradigm of prostate cancer
                                        diagnosis. Over the last couple of decades, its utilization has steadily
                                        increased, becoming an integral part of diagnosing and treating patients
                                        with suspected or confirmed prostate cancer. It is now employed from the
                                        initial cancer detection to defining the most appropriate treatment for
                                        patients and evaluating disease recurrence. Additionally, MRI can rule
                                        out the presence of clinically significant prostate cancers, preventing
                                        many men from having an unnecessary invasive biopsy procedure and
                                        mitigating the overdiagnosis and overtreatment of insignificant
                                        cancers.</span></p>
                                <p dir="ltr"><span>The ACR is a national professional organization serving more
                                        than 36,000 diagnostic and interventional radiologists, radiation
                                        oncologists, nuclear medicine physicians and medical physicists with
                                        programs focusing on the practice of medical imaging, radiation oncology
                                        and the delivery of comprehensive health care services. Founded in 1924,
                                        the ACR is one of the largest and most influential medical associations
                                        in the United States. The ACR devotes its resources to making imaging
                                        and radiation therapy safe, effective and accessible to those who need
                                        it.</span></p>
                                <p><span>&nbsp;</span></p>
                            </div>

                            <div class="post-navigation">
                                <!--<a href="/news" class="post-prev">
                                            <div class="post-prev-title"><span>Previous Post</span></div>
                                        </a>
											-->
                                <a href="../news.php" class="post-all" style="width:200px;">
                                    Find More Articles
                                </a>
                                <!--
										<a href="blog-single-video.php" class="post-next">
                                            <div class="post-next-title"><span>Next Post</span></div>
                                        </a>
											-->
                            </div>

                        </div>
                    </div>

                    <!-- end: Post single item-->
                </div>
            </div>
        </div>
    </div>
</section>


<?php
include "../footer.php"
?>