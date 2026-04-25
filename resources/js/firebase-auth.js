import { initializeApp } from 'firebase/app';
import { getAuth, GoogleAuthProvider, signInWithPopup } from 'firebase/auth';

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

window.signInWithGoogle = async function () {
    const btn   = document.getElementById('btn-google-signin');
    const error = document.getElementById('firebase-error');

    if (btn)   btn.disabled = true;
    if (error) error.classList.add('hidden');

    try {
        const provider = new GoogleAuthProvider();
        const result   = await signInWithPopup(auth, provider);
        const idToken  = await result.user.getIdToken();

        document.getElementById('firebase-id-token').value = idToken;
        document.getElementById('firebase-token-form').submit();
    } catch (err) {
        if (btn) btn.disabled = false;
        if (error) {
            const messages = {
                'auth/popup-closed-by-user':   'Cerraste la ventana antes de completar el inicio de sesión.',
                'auth/cancelled-popup-request':'La solicitud fue cancelada.',
                'auth/popup-blocked':          'El navegador bloqueó la ventana emergente. Permítela e intenta de nuevo.',
            };
            error.textContent = messages[err.code] ?? 'Error al iniciar sesión con Google. Intenta de nuevo.';
            error.classList.remove('hidden');
        }
    }
};
