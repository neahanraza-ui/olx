<?php
// includes/cookie-banner.php
?>
<div id="cookie-banner" class="cookie-banner" style="display:none;">
    <div class="cookie-content">
        <div class="cookie-text">
            <h4><i class="fas fa-cookie-bite"></i> We value your privacy</h4>
            <p>We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.</p>
        </div>
        <div class="cookie-actions">
            <button class="btn btn-ghost btn-sm" onclick="toggleCookieDetails()">Customize</button>
            <button class="btn btn-primary btn-sm" onclick="acceptAllCookies()">Accept All</button>
        </div>
    </div>

    <div id="cookie-details" class="cookie-details" style="display:none;">
        <div class="cookie-option">
            <div>
                <strong>Necessary</strong>
                <p>Required for the site to function properly.</p>
            </div>
            <input type="checkbox" checked disabled>
        </div>
        <div class="cookie-option">
            <div>
                <strong>Analytics</strong>
                <p>Help us understand how visitors interact with the site.</p>
            </div>
            <input type="checkbox" id="cookie-analytics">
        </div>
        <div class="cookie-option">
            <div>
                <strong>Preferences</strong>
                <p>Allow the site to remember choices you make.</p>
            </div>
            <input type="checkbox" id="cookie-preferences">
        </div>
        <div class="cookie-option">
            <div>
                <strong>Marketing</strong>
                <p>Used to track visitors across websites for personalized ads.</p>
            </div>
            <input type="checkbox" id="cookie-marketing">
        </div>
        <div style="text-align:right; margin-top:15px;">
            <button class="btn btn-primary btn-sm" onclick="saveCookiePreferences()">Save Preferences</button>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 10000;
    max-width: 600px;
    margin: 0 auto;
}
.cookie-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
.cookie-text h4 { margin: 0 0 5px 0; display: flex; align-items: center; gap: 8px; }
.cookie-text p { margin: 0; font-size: 0.85rem; color: var(--text-3); }
.cookie-actions { display: flex; gap: 10px; flex-shrink: 0; }
.cookie-details {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}
.cookie-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
}
.cookie-option strong { display: block; font-size: 0.9rem; }
.cookie-option p { margin: 0; font-size: 0.75rem; color: var(--text-3); }
@media (max-width: 600px) {
    .cookie-content { flex-direction: column; text-align: center; }
    .cookie-actions { width: 100%; }
    .cookie-actions .btn { flex: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!getCookie('cookie_consent')) {
        document.getElementById('cookie-banner').style.display = 'block';
    }
});

function toggleCookieDetails() {
    const details = document.getElementById('cookie-details');
    details.style.display = details.style.display === 'none' ? 'block' : 'none';
}

function acceptAllCookies() {
    setCookieConsent({ analytics: true, preferences: true, marketing: true });
}

function saveCookiePreferences() {
    setCookieConsent({
        analytics: document.getElementById('cookie-analytics').checked,
        preferences: document.getElementById('cookie-preferences').checked,
        marketing: document.getElementById('cookie-marketing').checked
    });
}

function setCookieConsent(preferences) {
    setCookie('cookie_consent', JSON.stringify(preferences), 365);
    document.getElementById('cookie-banner').style.display = 'none';
    
    // Optional: Send to server if logged in
    fetch('/olx1/ajax/save-cookie-consent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(preferences)
    });
}

function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i=0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}
</script>
