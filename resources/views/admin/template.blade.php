<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <title>@yield('title') | {{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="description" content="@yield('description')" />
    <meta name="keywords" content="@yield('keywords')" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="tr_TR" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="" />
    <meta property="og:url" content="" />
    <meta property="og:site_name" content="" />
    <link rel="canonical" href="{{ route('admin.dashboard.index') }}" />
    <link rel="shortcut icon" href="" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{ assetAdmin('') }}/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet"
        type="text/css" />
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ assetAdmin('') }}/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="{{ assetAdmin('') }}/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <link href="{{ assetAdmin('') }}/css/custom.css" rel="stylesheet" type="text/css" />

    @yield('styles')
    @stack('styles')
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>

    @livewireStyles


</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
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
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            @include('admin.static.header')
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                @include('admin.static.sidebar')
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <!--begin::Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        @yield('breadcrumb')
                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <!--begin::Content container-->
                            <div id="kt_app_content_container" class="app-container container-xxl">
                                @yield('master')
                            </div>
                            <!--end::Content container-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Content wrapper-->
                    @include('admin.static.footer')
                </div>
                <!--end:::Main-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->

    <form action="{{ route('admin.auth.logout-post') }}" method="POST" id="logOutForm">
        @csrf
    </form>

    <!--begin::Javascript-->
    <script>
        var hostUrl = "{{ url('') }}/netAdmin/";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ assetAdmin('') }}/plugins/global/plugins.bundle.js"></script>
    <script src="{{ assetAdmin('') }}/js/scripts.bundle.js"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>
    <script src="{{ assetAdmin('plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <!--end::Vendors Javascript-->
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ assetAdmin('js/widgets.bundle.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/widgets.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/utilities/modals/create-app.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/utilities/modals/new-target.js') }}"></script>
    <script src="{{ assetAdmin('js/custom/utilities/modals/users-search.js') }}"></script>
    <script>
        const defaultDateFormat = () => {
            return "D/M/Y";
        };
        const defaultDateTimeFormat = () => {
            return "D/M/Y H:i:s";
        };

        const itiOptions = (hiddenInput, extraParams = {}) => {
            let options = {
                utilsScript: "{{ asset('js/plugins/intl-tel-input/intlTelInput-utils.js') }}",
                onlyCountries: ["tr"],
                separateDialCode: true,
                hiddenInput: hiddenInput,
                nationalMode: true,
                allowDropdown: false,
                formatOnDisplay: false
            };
            Object.keys(extraParams).forEach(key => {
                options[key] = extraParams[key];
            });

            return options;
        }

        const swal = {
            success: function({
                icon = 'success',
                title = '',
                message,
                showConfirmButton = 0,
                showCancelButton = 1,
                allowOutsideClick = false,
                confirmButtonText = '',
                cancelButtonText = 'Kapat',
            }) {
                return Swal.fire({
                    icon: icon,
                    title: title,
                    html: message,
                    showConfirmButton: showConfirmButton,
                    showCancelButton: showCancelButton,
                    allowOutsideClick: allowOutsideClick,
                    buttonsStyling: false,
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: cancelButtonText,
                    customClass: {
                        confirmButton: "btn btn-primary btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                });
            },
            error: function({
                icon = 'error',
                title = '',
                message,
                showConfirmButton = 0,
                showCancelButton = 1,
                allowOutsideClick = false,
                confirmButtonText = '',
                cancelButtonText = 'Kapat',
            }) {
                return Swal.fire({
                    icon: icon,
                    title: title,
                    html: message,
                    showConfirmButton: showConfirmButton,
                    showCancelButton: showCancelButton,
                    allowOutsideClick: allowOutsideClick,
                    buttonsStyling: false,
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: cancelButtonText,
                    customClass: {
                        confirmButton: "btn btn-primary btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                });
            }
        }

        $.extend(true, $.fn.dataTable.defaults, {
            "language": {
                "url": "{{ assetAdmin('plugins/custom/datatables/tr.json') }}"
            }
        });
    </script>
    <script src="{{ assetAdmin('') }}/js/custom.js"></script>
    <!--end::Custom Javascript-->

    @vite(['resources/js/app.js'])

    @livewireScripts

    @yield('scripts')

    @stack('scripts')
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>
