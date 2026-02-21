<!-- Security Modal -->
<div id="securityModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeSecurityModal()">&times;</button>
        <h2 style="text-align: center;">Two-Factor Authentication</h2>

        <div id="securityLoading" style="text-align: center; padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
            <p>Loading security details...</p>
        </div>

        <div id="securityContent" style="display: none;">
            <div id="2faNotEnabled">
                <p style="margin-bottom: 1.5rem; color: var(--text-muted);">
                    Enhance your account security by enabling Two-Factor Authentication (2FA) using Google
                    Authenticator.
                </p>
                <div
                    style="text-align: center; margin-bottom: 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <!-- QR Code Container -->
                    <div id="securityQrContainer"
                        style="padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    </div>

                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Scan this QR code
                        with your authenticator app</p>
                </div>

                <div class="form-group">
                    <label for="verifyCode">Verification Code</label>
                    <input type="text" id="verifyCode" class="form-control" placeholder="Enter 6-digit code"
                        maxlength="6" autocomplete="off">
                </div>
                <button class="btn btn-primary" style="width: 100%;" onclick="enable2FA()">Enable 2FA</button>
            </div>

            <div id="2faEnabled" style="display: none; text-align: center;">
                <i class="fa-solid fa-check-circle"
                    style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 1rem;">2FA is Enabled</h3>
                <p style="color: var(--text-muted);">Your account is secured with two-factor authentication.</p>
                <!-- Optional: Add Disable 2FA button here if needed in future -->
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    const securityModal = document.getElementById('securityModal');

    function openSecurityModal() {
        securityModal.classList.add('active');
        loadSecurityDetails();
    }

    function closeSecurityModal() {
        securityModal.classList.remove('active');
    }

    async function loadSecurityDetails() {
        const loading = document.getElementById('securityLoading');
        const content = document.getElementById('securityContent');
        const notEnabled = document.getElementById('2faNotEnabled');
        const enabled = document.getElementById('2faEnabled');
        const qrContainer = document.getElementById('securityQrContainer');

        loading.style.display = 'block';
        content.style.display = 'none';

        try {
            const response = await fetch('/api/setup_2fa.php', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                loading.style.display = 'none';
                content.style.display = 'block';

                if (data.enabled) {
                    notEnabled.style.display = 'none';
                    enabled.style.display = 'block';
                } else {
                    notEnabled.style.display = 'block';
                    enabled.style.display = 'none';

                    // Render QR Code
                    qrContainer.innerHTML = ''; // Clear previous
                    if (data.otpauth_url) {
                        new QRCode(qrContainer, {
                            text: data.otpauth_url,
                            width: 150,
                            height: 150,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }
                }
            } else {
                showToast('Failed to load security details', 'error');
                closeSecurityModal();
            }
        } catch (error) {
            console.error('Security load error:', error);
            showToast('Error loading security details', 'error');
            closeSecurityModal();
        }
    }

    // Handle Enter key for verification input
    document.getElementById('verifyCode').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            enable2FA();
        }
    });

    async function enable2FA() {
        const codeInput = document.getElementById('verifyCode');
        const code = codeInput.value.trim();

        if (!code) {
            showToast('Please enter the verification code', 'error');
            return;
        }

        const btn = document.querySelector('#securityContent button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

        try {
            const response = await fetch('/api/verify_2fa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });

            // Handle non-JSON responses (e.g., server errors)
            let data;
            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Invalid server response');
            }

            if (data.success) {
                showToast('2FA Enabled Successfully!', 'success');
                loadSecurityDetails(); // Refresh view

                // Clear input
                codeInput.value = '';
            } else {
                showToast(data.error || 'Verification failed. Please try again.', 'error');
                // Don't clear input on error to allow user to edit
                if (data.error && data.error.includes('expired')) {
                    // specific handling if needed
                }
            }
        } catch (error) {
            console.error('2FA Enable Error:', error);
            showToast('Connection error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
</script>