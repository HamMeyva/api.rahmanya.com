<!DOCTYPE html>
<!--
Author: Keenthemes
Product Name: Metronic
Product Version: 8.2.1
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
<!--begin::Head-->

<head>
    <base href="../../../" />
    <title>Giriş Yap | {{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="description"
        content="The most advanced Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel versions. Grab your copy now and get life-time updates for free." />
    <meta name="keywords"
        content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title"
        content="Shoot90 - Yönetim Paneli Giriş Ekranı" />
    <meta property="og:url" content="{{ route('admin.dashboard.index') }}" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ route('admin.dashboard.index') }}" />
    <link rel="shortcut icon" href="" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ assetAdmin('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ assetAdmin('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Body-->
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <!--begin::Form-->
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <!--begin::Wrapper-->
                    <div class="w-lg-500px p-10">
                        <!--begin::Form-->
                        <form class="form w-100" id="loginForm">
                            @csrf
                            <!--begin::Heading-->
                            <div class="text-center mb-11">
                                <!--begin::Title-->
                                <h1 class="text-gray-900 fw-bolder mb-3">Giriş Yap</h1>
                                <!--end::Title-->
                                <!--begin::Subtitle-->
                                <div class="text-gray-500 fw-semibold fs-6"></div>
                                <!--end::Subtitle=-->
                            </div>
                            <!--begin::Heading-->
                            <!--begin::Input group=-->
                            <div class="fv-row mb-8">
                                <!--begin::Email-->
                                <input type="text" placeholder="E-Posta" name="email" autocomplete="off"
                                    class="form-control bg-transparent" value="info@asiste.com.tr" />
                                <!--end::Email-->
                            </div>
                            <!--end::Input group=-->
                            <div class="fv-row mb-3">
                                <!--begin::Password-->
                                <input type="password" placeholder="Parola" name="password" autocomplete="off"
                                    class="form-control bg-transparent" value="Batuhan.123" />
                                <!--end::Password-->
                            </div>
                            <!--end::Input group=-->
                            <!--begin::Submit button-->
                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Giriş Yap</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Lütfen bekleyiniz...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </button>
                            </div>
                            <!--end::Submit button-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Form-->
                <!--begin::Footer-->
                <div class="w-lg-500px d-flex flex-stack px-10 mx-auto d-none">
                    <!--begin::Languages-->
                    <div class="me-10">
                        <!--begin::Toggle-->
                        <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start"
                            data-kt-menu-offset="0px, 0px">
                            <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3"
                                src="{{ assetAdmin('') }}/media/flags/united-states.svg" alt="" />
                            <span data-kt-element="current-lang-name" class="me-1">English</span>
                            <span class="d-flex flex-center rotate-180">
                                <i class="ki-duotone ki-down fs-5 text-muted m-0"></i>
                            </span>
                        </button>
                        <!--end::Toggle-->
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7"
                            data-kt-menu="true" id="kt_auth_lang_menu">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link d-flex px-5" data-kt-lang="English">
                                    <span class="symbol symbol-20px me-4">
                                        <img data-kt-element="lang-flag" class="rounded-1"
                                            src="{{ assetAdmin('') }}/media/flags/united-states.svg" alt="" />
                                    </span>
                                    <span data-kt-element="lang-name">English</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link d-flex px-5" data-kt-lang="Spanish">
                                    <span class="symbol symbol-20px me-4">
                                        <img data-kt-element="lang-flag" class="rounded-1"
                                            src="{{ assetAdmin('') }}/media/flags/spain.svg" alt="" />
                                    </span>
                                    <span data-kt-element="lang-name">Spanish</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link d-flex px-5" data-kt-lang="German">
                                    <span class="symbol symbol-20px me-4">
                                        <img data-kt-element="lang-flag" class="rounded-1"
                                            src="{{ assetAdmin('') }}/media/flags/germany.svg" alt="" />
                                    </span>
                                    <span data-kt-element="lang-name">German</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link d-flex px-5" data-kt-lang="Japanese">
                                    <span class="symbol symbol-20px me-4">
                                        <img data-kt-element="lang-flag" class="rounded-1"
                                            src="{{ assetAdmin('') }}/media/flags/japan.svg" alt="" />
                                    </span>
                                    <span data-kt-element="lang-name">Japanese</span>
                                </a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->

                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Languages-->
                    <!--begin::Links-->
                    <div class="d-flex fw-semibold text-primary fs-base gap-5">
                        <a href="#" target="_blank">Terms</a>
                        <a href="#" target="_blank">Plans</a>
                        <a href="#" target="_blank">Contact Us</a>
                    </div>
                    <!--end::Links-->
                </div>
                <!--end::Footer-->
            </div>
            <!--end::Body-->
            <!--begin::Aside-->
            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
                style="background-image: url({{ assetAdmin('') }}/images/splash_background.png)">
                <!--begin::Content-->
{{--                 <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <!--begin::Logo-->
                    <a href="#" class="mb-0 mb-lg-12">
                        <img alt="Logo" src="{{ assetAdmin('') }}/images/icon.svg"
                            class="h-60px h-lg-75px" />
                    </a>
                    <!--end::Logo-->
                    <!--begin::Title-->
                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">Shoot90</h1>
                    <!--end::Title-->
                    <!--begin::Text-->
                    <div class="d-none d-lg-block text-white fs-base text-center">
                        Shoot90 Yönetim Paneli Giriş Ekranına Hoş Geldiniz.
                    </div>
                    <!--end::Text-->
                </div> --}}
                <!--end::Content-->
            </div>
            <!--end::Aside-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Root-->
    <!--begin::Javascript-->
    <script>
        var hostUrl = "{{ assetAdmin('') }}/";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ assetAdmin('plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ assetAdmin('js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <script>
        $(document).ready(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('submit', '#loginForm', function(e) {
                e.preventDefault()
                let form = $(this)
                $.ajax({
                    type: 'POST',
                    url: '{{ route('admin.auth.login-post') }}',
                    data: new FormData(this),
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        // propSubmitButton(form.find("button[type='submit']"), 1);
                        form.find("button[type='submit']").attr("data-kt-indicator", "on");
                        form.find("button[type='submit']").addClass('disabled')
                    },
                    success: function(res) {
                        Swal.fire({
                            title: "Başarılı",
                            text: 'Giriş Başarılı. Yönlendiriliyorsunuz...',
                            icon: "success",
                            showConfirmButton: 0,
                            allowOutsideClick: false,
                        })
                        setTimeout(() => {
                            window.location.href = res.redirectUrl;
                        }, 1000)
                    },
                    error: function(xhr, status, error) {
                        console.log("Error response:", xhr);
                        console.log("Status:", status);
                        console.log("Error:", error);
                        Swal.fire({
                            title: "Hata",
                            text: xhr.responseJSON?.message ?? '',
                            icon: "error",
                            allowOutsideClick: false,
                            showConfirmButton: 0,
                            showCancelButton: 1,
                            cancelButtonText: 'Kapat',
                            customClass: {
                                cancelButton: 'btn btn-secondary btn-sm'
                            }
                        })
                    },
                    complete: function() {
                        form.find("button[type='submit']").attr("data-kt-indicator", "off");
                        form.find("button[type='submit']").removeClass('disabled')
                    }
                })
            })
        })
    </script>
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>
