import { initializeApp } from 'firebase/app';
import {
    getAuth,
    GoogleAuthProvider,
    signInWithPopup,
    signInWithRedirect,
    getRedirectResult,
} from 'firebase/auth';

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId:     import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

const app  = initializeApp(firebaseConfig);
const auth = getAuth(app);

const ERROR_MESSAGES = {
    'auth/popup-closed-by-user':
        'Cerraste la ventana antes de completar el inicio de sesión.',
    'auth/cancelled-popup-request':
        'La solicitud fue cancelada. Intenta de nuevo.',
    'auth/popup-blocked':
        'El navegador bloqueó la ventana emergente. Activa los popups para este sitio e intenta de nuevo.',
    'auth/account-exists-with-different-credential':
        'Ya existe una cuenta con este correo usando otro método.',
    'auth/network-request-failed':
        'Error de red. Verifica tu conexión e intenta de nuevo.',
    'auth/user-disabled':
        'Esta cuenta ha sido deshabilitada.',
    'auth/operation-not-allowed':
        'Inicio de sesión con Google no está habilitado. Contacta al administrador.',
    'auth/invalid-api-key':
        'Error de configuración de Firebase. Contacta al administrador.',
};

function showError(msg) {
    const el = document.getElementById('firebase-error');
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
}

function resetBtn() {
    const btn = document.getElementById('btn-google-signin');
    if (!btn) return;
    btn.disabled    = false;
    btn.innerHTML   = `
        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google`;
}

// Fallback: si el popup fue bloqueado, intenta redirect y espera el resultado al volver
getRedirectResult(auth)
    .then(async (result) => {
        if (!result) return;

        const btn = document.getElementById('btn-google-signin');
        if (btn) {
            btn.disabled   = true;
            btn.textContent = 'Verificando cuenta…';
        }

        const idToken = await result.user.getIdToken();
        document.getElementById('firebase-id-token').value = idToken;
        document.getElementById('firebase-token-form').submit();
    })
    .catch((err) => {
        if (err.code && err.code !== 'auth/no-auth-event') {
            showError(ERROR_MESSAGES[err.code] ?? 'Error al completar el inicio de sesión. Intenta de nuevo.');
        }
        resetBtn();
    });

// Flujo principal: popup (sin intermediario, funciona en localhost y producción)
window.signInWithGoogle = async function () {
    const btn     = document.getElementById('btn-google-signin');
    const errorEl = document.getElementById('firebase-error');

    if (btn) btn.disabled = true;
    if (errorEl) errorEl.classList.add('hidden');

    const provider = new GoogleAuthProvider();
    provider.setCustomParameters({ prompt: 'select_account' });

    try {
        const result  = await signInWithPopup(auth, provider);

        if (btn) btn.textContent = 'Verificando cuenta…';

        const idToken = await result.user.getIdToken();
        document.getElementById('firebase-id-token').value = idToken;
        document.getElementById('firebase-token-form').submit();

    } catch (err) {
        // Popup bloqueado → fallback a redirect
        if (err.code === 'auth/popup-blocked') {
            await signInWithRedirect(auth, provider);
            return;
        }

        // El usuario cerró el popup voluntariamente → solo restaurar botón, sin ruido
        if (err.code === 'auth/popup-closed-by-user' ||
            err.code === 'auth/cancelled-popup-request') {
            resetBtn();
            return;
        }

        showError(ERROR_MESSAGES[err.code] ?? 'Error al iniciar sesión con Google. Intenta de nuevo.');
        resetBtn();
    }
};
