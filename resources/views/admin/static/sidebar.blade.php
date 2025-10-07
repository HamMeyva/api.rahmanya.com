<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6 justify-content-center" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="{{ route('admin.dashboard.index') }}">
            <img alt="Logo" src="{{ assetAdmin('images/brand.svg') }}"
                class="h-50px p-2 app-sidebar-logo-default" />
            <img alt="Logo" src="{{ assetAdmin('images/icon.svg') }}" class="h-20px app-sidebar-logo-minimize" />
        </a>
        <!--end::Logo image-->
        <!--begin::Sidebar toggle-->
        <!--begin::Minimized sidebar setup:
if (isset($_COOKIE["sidebar_minimize_state"]) && $_COOKIE["sidebar_minimize_state"] === "on") {
1. "src/js/layout/sidebar.js" adds "sidebar_minimize_state" cookie value to save the sidebar minimize state.
2. Set data-kt-app-sidebar-minimize="on" attribute for body tag.
3. Set data-kt-toggle-state="active" attribute to the toggle element with "kt_app_sidebar_toggle" id.
4. Add "active" class to to sidebar toggle element with "kt_app_sidebar_toggle" id.
}
-->
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-duotone ki-black-left-line fs-3 rotate-180">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->
    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <!--begin::Scroll wrapper-->
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
                    data-kt-menu="true" data-kt-menu-expand="false">
                    <x-admin.menu-item title="Ana Sayfa" icon="fa fa-gauge" :link="route('admin.dashboard.index')" />
                    <x-admin.menu-item :permission="['user list']" title="Kullanıcılar" icon="fa fa-users" :link="route('admin.users.index')" />
                    <x-admin.menu-item :permission="['video list']" title="Videolar" icon="fa fa-video" :link="route('admin.videos.index')" />
                    <x-admin.menu-item :permission="['story list']" title="Hikayeler" icon="fa fa-window-restore" :link="route('admin.stories.index')" />
                    <x-admin.menu-dropdown :permission="[
                        'music list',
                        'music category list',
                        'artist list'
                    ]" title="Müzikler" icon="fa fa-music" :routes="['admin.musics.index', 'admin.musics.categories.index', 'admin.artists.index']">
                        <x-admin.menu-dropdown-item :permission="['music list']" title="Müzikler" :link="route('admin.musics.index')" />
                        <x-admin.menu-dropdown-item :permission="['music category list']" title="Kategoriler" :link="route('admin.musics.categories.index')" />
                        <x-admin.menu-dropdown-item :permission="['artist list']" title="Sanatçılar" :link="route('admin.artists.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown :permission="[
                        'live stream list',
                        'live stream category list',
                        'live stream gift list'
                    ]" title="Canlı Yayınlar" icon="fa fa-broadcast-tower" :routes="['admin.live-streams.index', 'admin.live-streams.categories.index', 'admin.agora-channel-gifts.index']">
                        <x-admin.menu-dropdown-item :permission="['live stream list']" title="Yayınlar" :link="route('admin.live-streams.index')" />
                        <x-admin.menu-dropdown-item :permission="['live stream category list']" title="Kategoriler" :link="route('admin.live-streams.categories.index')" />
                        <x-admin.menu-dropdown-item :permission="['live stream gift list']" title="Hediyeler" :link="route('admin.agora-channel-gifts.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown :permission="['challenge list']" title="Müsabakalar" icon="fa fa-futbol" :routes="['admin.challenges.index']">
                        <x-admin.menu-dropdown-item :permission="['challenge list']" title="Müsabakalar" :link="route('admin.challenges.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown :permission="['payment list', 'payment waiting approval']" title="Ödemeler" icon="fa fa-credit-card" :routes="['admin.payments.index', 'admin.payments.waiting-approval']">
                        <x-admin.menu-dropdown-item :permission="['payment list']" title="Ödemeler" :link="route('admin.payments.index')" />
                        <x-admin.menu-dropdown-item :permission="['payment waiting approval']" title="Onay Bekleyen Havaleler" :link="route('admin.payments.waiting-approval')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown :permission="['gift list']" title="Hediyeler" icon="fa fa-gift" :routes="['admin.gifts.index']">
                        <x-admin.menu-dropdown-item :permission="['gift list']" title="Hediyeler" :link="route('admin.gifts.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-item :permission="['report problem list']" title="Şikayetler" icon="fa fa-exclamation" :link="route('admin.report-problems.index')" />
                    <x-admin.menu-item :permission="['bulk notification list']" title="Toplu Bildirim" icon="fa fa-bell" :link="route('admin.bulk-notifications.index')" />
                    <x-admin.menu-item :permission="['coupon code list']" title="Kupon Kodları" icon="fa fa-gift" :link="route('admin.coupons.index')" />
                    <x-admin.menu-dropdown title="Reklam Yönetimi" icon="fa fa-ad" :routes="['admin.advertisers.index', 'admin.ads.index']">
                        <x-admin.menu-dropdown-item title="Reklam Verenler" :link="route('admin.advertisers.index')" />
                        <x-admin.menu-dropdown-item title="Reklamlar" :link="route('admin.ads.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown title="Shoot Coinler" icon="fa fa-coins" :routes="[
                        'admin.coin-packages.index',
                        'admin.coin-withdrawal-prices.index',
                        'admin.coin-withdrawal-requests.index',
                    ]">
                        <x-admin.menu-dropdown-item title="Satış Paketleri" :link="route('admin.coin-packages.index')" />
                        <x-admin.menu-dropdown-item title="Çekim Fiyatları" :link="route('admin.coin-withdrawal-prices.index')" />
                        <x-admin.menu-dropdown-item title="Çekim Talepleri" :link="route('admin.coin-withdrawal-requests.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown title="Raporlar" icon="fa fa-chart-line" :routes="[
                        'admin.reports.videos.content-performances.index',
                        'admin.reports.users.demographics.index',
                        'admin.reports.users.video-view-durations.index',
                        'admin.reports.users.engagement-metrics.index',
                        'admin.reports.live-streams.index',
                    ]">
                        <x-admin.menu-dropdown title="Videolar" icon="bullet bullet-dot" :routes="['admin.reports.videos.content-performances.index']">
                            <x-admin.menu-dropdown-item title="İçerik Performans" :link="route('admin.reports.videos.content-performances.index')" />
                        </x-admin.menu-dropdown>
                        <x-admin.menu-dropdown title="Kullanıcılar" icon="bullet bullet-dot" :routes="[
                            'admin.reports.users.demographics.index',
                            'admin.reports.users.video-view-durations.index',
                            'admin.reports.users.engagement-metrics.index',
                        ]">
                            <x-admin.menu-dropdown-item title="Demografik" :link="route('admin.reports.users.demographics.index')" />
                            <x-admin.menu-dropdown-item title="Video İzlemeleri" :link="route('admin.reports.users.video-view-durations.index')" />
                            <x-admin.menu-dropdown-item title="Katılım Metrikleri" :link="route('admin.reports.users.engagement-metrics.index')" />
                        </x-admin.menu-dropdown>
                        <x-admin.menu-dropdown-item title="Canlı Yayınlar" :link="route('admin.reports.live-streams.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown title="Panel" icon="fa fa-solar-panel"
                        :permission="['admin list', 'role list']"
                        :routes="['admin.admins.index', 'admin.roles.index']">
                        <x-admin.menu-dropdown-item :permission="['admin list']" title="Kullanıcılar" :link="route('admin.admins.index')" />
                        <x-admin.menu-dropdown-item :permission="['role list']" title="Roller" :link="route('admin.roles.index')" />
                    </x-admin.menu-dropdown>
                    <x-admin.menu-dropdown title="Ayarlar" icon="fa fa-gear" :routes="[
                        'admin.settings.teams.index',
                        'admin.settings.punishments.index',
                        'admin.settings.popular-searches.index',
                        'admin.settings.app-settings.index',
                        'admin.settings.banned-words.index',
                    ]">
                        <x-admin.menu-dropdown-item title="Takımlar" :link="route('admin.settings.teams.index')" />
                        <x-admin.menu-dropdown-item title="Cezalar" :link="route('admin.settings.punishments.index')" />
                        <x-admin.menu-dropdown-item title="Popüler Aramalar" :link="route('admin.settings.popular-searches.index')" />
                        <x-admin.menu-dropdown-item title="Yasaklı Kelimeler" :link="route('admin.settings.banned-words.index')" />
                        <x-admin.menu-dropdown-item title="Sistem Ayarları" :link="route('admin.settings.app-settings.index')" />
                    </x-admin.menu-dropdown>
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Scroll wrapper-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->
    <!--begin::Footer-->
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <a href="javascript:void(0);" onclick="document.getElementById('logOutForm').submit()"
            class="btn btn-flex flex-center btn-custom overflow-hidden text-nowrap px-0 h-40px w-100">
            <i class="fa fa-right-from-bracket"></i>
            <span class="btn-label">Çıkış Yap</span>
        </a>
    </div>
    <!--end::Footer-->
</div>
<!--end::Sidebar-->