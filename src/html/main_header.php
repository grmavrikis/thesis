<?php
$scope = $auth->getAccessScope();
?>
<input type="hidden" id="language_code" value="<?php echo $language['code']; ?>">
<header>
    <div class="mainwrapper">
        <div class="top-banner">

            <div class="top-banner-info">
                <span><?php echo TEXT_SYSTEM_NAME; ?></span>
                <span><a href="tel:+30210XXXXXXX">Τηλ.: +30 210 XXXX XXX</a></span>
            </div>
            <div class="top-banner-login">
                <?php
                // Language Selection Dropdown
                $defaultLanguage = $languages->getDefaultLanguage();
                $availableLanguages = $languages->getLanguages();
                if (count($availableLanguages) > 1):
                ?>
                    <div class="language-dropdown">
                        <button class="language-button">
                            <img src="<?php echo htmlspecialchars($language['flag']); ?>" alt="<?php echo htmlspecialchars($language['name']); ?>" class="flag-icon">
                        </button>

                        <div class="language-dropdown-content">
                            <?php foreach ($availableLanguages as $lang): ?>
                                <a href="<?php echo $seoUrl->generate($_SERVER['PHP_SELF'], $_GET, $lang['code']); ?>" <?php echo ($lang['code'] === $language['code']) ? 'rel="nofollow"' : ''; ?>>
                                    <?php echo strtoupper(htmlspecialchars($lang['code'])); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div id="loginFormContainer" class="login-form-container">
                    <span class="close-login" id="closeLogin">&times;</span>
                    <form id="loginForm">
                        <h2><?php echo TEXT_CLIENT_LOGIN; ?></h2>
                        <input type="text" id="loginUsername" name="username" placeholder="<?php echo TEXT_USERNAME; ?>" autocomplete="username" required>
                        <input type="password" id="loginPassword" name="password" placeholder="<?php echo TEXT_PASSWORD; ?>" autocomplete="new-password" required>

                        <button type="submit" class="login-button"><?php echo TEXT_LOGIN; ?></button>
                        <p id="loginMessage" class="loginMessage"></p>
                    </form>
                </div>
                <?php if ($scope === 'public'): ?>
                    <a href="<?php echo $seoUrl->generate('create-account.php'); ?>" id="registerButton" class="login-button register-button"><?php echo TEXT_CLIENT_REGISTER; ?></a>
                    <button id="loginButton" class="login-button"><?php echo TEXT_CLIENT_LOGIN; ?></button>
                <?php elseif ($scope === 'client'): ?>
                    <span class="nav-link">
                        <a href="<?php echo $seoUrl->generate('portal/account.php'); ?>" class="login-button account-button"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    </span>
                    <button class="login-button" id="logoutButton"><?php echo TEXT_LOGOUT; ?></button>
                <?php elseif ($scope === 'admin'): ?>
                    <span class="nav-link">
                        <a href="<?php echo $seoUrl->generate('administration/admin_account.php'); ?>" class="login-button account-button"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    </span>
                    <button class="login-button" id="adminLogoutButton"><?php echo TEXT_LOGOUT; ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>