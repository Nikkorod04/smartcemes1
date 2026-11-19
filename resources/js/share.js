import QRCode from 'qrcode';

/**
 * Draw a share-link QR code into a canvas.
 *
 * Done in the browser on purpose: a QR-image service would receive every
 * share token in the URL, and those tokens are the only credential protecting
 * the public form. The canvas is rendered at 2x and scaled down with CSS so it
 * stays sharp when printed.
 */
window.scDrawQr = function (canvas, text) {
    if (!canvas || !text) {
        return;
    }

    QRCode.toCanvas(canvas, text, {
        width: 440,
        margin: 2,
        errorCorrectionLevel: 'M',
        color: { dark: '#0a2a66', light: '#ffffff' },
    }).then(() => {
        // Drawn at 2x for print sharpness, then shown at the requested size —
        // 200px on the share screen, 280px on the A4 sheet. Set it here rather
        // than in CSS: the library owns the canvas attributes, and an explicit
        // inline size cannot be lost to attribute precedence.
        const size = Number(canvas.dataset.qrSize) || 200;
        canvas.style.width = size + 'px';
        canvas.style.height = size + 'px';
    }).catch(() => {
        // A failed draw leaves a blank square; the URL beside it is the fallback.
    });
};

/** Copy through a hidden textarea — the async API needs a focused document. */
function scLegacyCopy(text) {
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '0';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch (e) {
        copied = false;
    }

    document.body.removeChild(field);

    return copied;
}

/**
 * Copy a link. Prefer the async clipboard API, but fall back when it is
 * missing *or refuses* — it rejects without document focus, and in some
 * embedded browsers it is blocked outright. The fallback still needs the
 * click that got us here, which is why it runs inside the same handler.
 */
window.scCopyLink = async function (text) {
    if (!text) {
        return;
    }

    let copied = false;

    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            copied = true;
        } catch (e) {
            copied = scLegacyCopy(text);
        }
    } else {
        copied = scLegacyCopy(text);
    }

    if (copied) {
        window.SC?.toast('Link copied', 'success');
    } else {
        window.SC?.toast('Could not copy — select the link and copy it by hand', 'warn');
    }
};
