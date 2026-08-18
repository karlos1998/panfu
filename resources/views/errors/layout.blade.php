<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, follow">

        <title>@yield('title') - Panfu.me</title>

        <link rel="apple-touch-icon" sizes="180x180" href="/vendor/panfu-me/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/vendor/panfu-me/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/vendor/panfu-me/favicons/favicon-16x16.png">

        <style>
            @font-face {
                font-family: "Font Awesome 6 Brands";
                src: url("/vendor/panfu-me/assets/fa-brands-400-D_cYUPeE.woff2") format("woff2");
                font-display: block;
                font-style: normal;
                font-weight: 400;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html,
            body {
                min-height: 100%;
                margin: 0;
            }

            body {
                color: #212529;
                background: #a9d21d;
                font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .panfu-error-shell {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                background: #a9d21d;
            }

            .panfu-error-header {
                min-height: 80px;
                flex: 0 0 auto;
                background: #5cd4ff;
            }

            .panfu-error-header__inner,
            .panfu-error-footer__inner {
                width: min(980px, calc(100% - 24px));
                margin: 0 auto;
            }

            .panfu-error-header__inner {
                min-height: 80px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }

            .panfu-error-logo {
                display: inline-flex;
                align-items: center;
            }

            .panfu-error-logo img {
                width: 192px;
                height: 64px;
                display: block;
            }

            .panfu-error-logo:hover {
                opacity: 0.5;
            }

            .panfu-error-socials {
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }

            .panfu-error-socials a {
                width: 30px;
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: rgba(255, 255, 255, 0.55);
                font-family: "Font Awesome 6 Brands";
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 1;
                text-decoration: none;
            }

            .panfu-error-socials a:hover {
                color: rgba(255, 255, 255, 0.75);
            }

            .panfu-error-main {
                min-height: 325px;
                flex: 1 0 auto;
                padding: 24px 0 60px;
                background-color: #a9d21d;
                background-image:
                    url("/vendor/panfu-me/assets/bg-trees-C3W6vnYt.png"),
                    url("/vendor/panfu-me/assets/background-C_gQ2x6Z.png");
                background-position: top center;
                background-repeat: no-repeat, repeat-x;
            }

            .panfu-error-card {
                width: min(480px, calc(100% - 24px));
                margin: 0 auto;
                padding: 20px;
                background: #ffffff;
                border: 1px solid rgba(0, 0, 0, 0.125);
                border-radius: 8px;
            }

            .panfu-error-card h1 {
                margin: 0 0 8px;
                color: #212529;
                font-size: 20px;
                font-weight: 700;
                line-height: 1.2;
            }

            .panfu-error-card p {
                margin: 0 0 16px;
                color: #5c6064;
                font-size: 14.4px;
                line-height: 1.5;
            }

            .panfu-error-card__button {
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 7px 12px;
                color: #82b315;
                background: #ffffff;
                border: 1px solid #82b315;
                border-radius: 8px;
                font-size: 14.4px;
                line-height: 1.5;
                text-decoration: none;
            }

            .panfu-error-card__button:hover {
                color: #ffffff;
                background: #92bd1d;
            }

            .panfu-error-footer {
                min-height: 89px;
                flex: 0 0 auto;
                color: #ffffff;
                background: #92bd1d;
            }

            .panfu-error-footer__inner {
                min-height: 89px;
                display: flex;
                align-items: center;
            }

            .panfu-error-footer p {
                margin: 0;
                font-size: 14.4px;
                line-height: 1.5;
            }

            @media (max-width: 520px) {
                .panfu-error-header,
                .panfu-error-header__inner {
                    min-height: 72px;
                }

                .panfu-error-logo img {
                    width: 132px;
                    height: auto;
                }

                .panfu-error-socials a {
                    width: 26px;
                }

                .panfu-error-main {
                    padding-top: 16px;
                }

                .panfu-error-footer__inner {
                    padding: 18px 0;
                }
            }

            @media (max-width: 350px) {
                .panfu-error-header__inner {
                    gap: 4px;
                }

                .panfu-error-logo img {
                    width: 118px;
                }

                .panfu-error-socials a {
                    width: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="panfu-error-shell">
            <header class="panfu-error-header">
                <div class="panfu-error-header__inner">
                    <a class="panfu-error-logo" href="/" aria-label="Panfu home">
                        <img src="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg" alt="Panfu">
                    </a>

                    <nav class="panfu-error-socials" aria-label="Social links">
                        <a href="https://www.facebook.com/Panfu.me/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">&#xf39e;</a>
                        <a href="https://www.instagram.com/teampanfu/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">&#xf16d;</a>
                        <a href="https://x.com/teampanfu" target="_blank" rel="noopener noreferrer" aria-label="X">&#xe61f;</a>
                        <a href="https://www.youtube.com/@teampanfu" target="_blank" rel="noopener noreferrer" aria-label="YouTube">&#xf167;</a>
                        <a href="https://www.tiktok.com/@teampanfu" target="_blank" rel="noopener noreferrer" aria-label="TikTok">&#xe07b;</a>
                        <a href="https://discord.gg/6sRx62m6RK" target="_blank" rel="noopener noreferrer" aria-label="Discord">&#xf392;</a>
                    </nav>
                </div>
            </header>

            <main class="panfu-error-main" data-error-status="@yield('code')">
                <section class="panfu-error-card" aria-labelledby="error-heading">
                    <h1 id="error-heading">@yield('heading')</h1>
                    <p>@yield('message')</p>
                    <a class="panfu-error-card__button" href="/">Return to home</a>
                </section>
            </main>

            <footer class="panfu-error-footer">
                <div class="panfu-error-footer__inner">
                    <div>
                        <p>&copy; 2016-{{ date('Y') }} <strong>Panfu.me</strong>. All rights reserved.</p>
                        <p>Panfu.me is not affiliated with or endorsed by Goodbeans GmbH.</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
