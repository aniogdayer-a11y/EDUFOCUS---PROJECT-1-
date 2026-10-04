const scene = document.querySelector('.auth-study-scene');

if (scene) {
    const pupils = [...scene.querySelectorAll('[data-cat-pupil]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let pointer = null;
    let frame = null;

    const drawEyes = () => {
        frame = null;
        const matrix = scene.getScreenCTM();
        if (!matrix) return;
        const target = pointer && !reducedMotion.matches
            ? new DOMPoint(pointer.x, pointer.y).matrixTransform(matrix.inverse())
            : null;

        pupils.forEach((pupil) => {
            let x = 0;
            let y = 0;
            if (target) {
                const dx = target.x - Number(pupil.dataset.eyeX);
                const dy = target.y - Number(pupil.dataset.eyeY);
                const distance = Math.hypot(dx, dy);
                const strength = Math.min(distance / 150, 1);
                if (distance > 0) {
                    x = (dx / distance) * 17 * strength;
                    y = (dy / distance) * 12 * strength;
                }
            }
            pupil.setAttribute('transform', `translate(${x.toFixed(2)} ${y.toFixed(2)})`);
        });
    };
    const scheduleDraw = () => {
        if (frame === null) frame = window.requestAnimationFrame(drawEyes);
    };
    const resetEyes = () => { pointer = null; scheduleDraw(); };

    window.addEventListener('pointermove', (event) => {
        pointer = { x: event.clientX, y: event.clientY };
        scheduleDraw();
    }, { passive: true });
    document.documentElement.addEventListener('pointerleave', resetEyes);
    window.addEventListener('blur', resetEyes);
    window.addEventListener('resize', scheduleDraw, { passive: true });
    window.addEventListener('scroll', scheduleDraw, { passive: true });
    reducedMotion.addEventListener('change', scheduleDraw);
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    button.hidden = false;
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        const label = input.name === 'password_confirmation' ? 'confirm password' : 'password';
        button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${label}`);
    });
});
