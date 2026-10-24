(() => {
    const nav = document.querySelector('.main-nav');

    if (!nav) return;

    const links = [...nav.querySelectorAll('a[href^="#"]')];
    const sections = links
        .map((link) => document.getElementById(link.hash.slice(1)))
        .filter((section) => section instanceof HTMLElement);

    if (sections.length !== links.length) {
        console.warn('EcoCycle navigation has a link without a matching section.');
    }

    const activate = (id) => {
        let matched = false;

        links.forEach((link) => {
            const active = link.hash === `#${id}`;
            matched ||= active;
            link.classList.toggle('active', active);

            if (active) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        return matched;
    };

    let navigationScroll = false;
    let navigationReleaseTimer;
    let scrollFrame = 0;

    const updateFromScroll = () => {
        scrollFrame = 0;

        if (navigationScroll) return;

        const atPageBottom = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2;

        if (atPageBottom) {
            activate(sections[sections.length - 1]?.id || 'home');
            return;
        }

        const marker = Math.min(220, Math.max(100, window.innerHeight * 0.32));
        const current = sections.reduce((activeSection, section) => {
            return section.getBoundingClientRect().top <= marker ? section : activeSection;
        }, sections[0]);

        if (current) activate(current.id);
    };

    const scheduleScrollUpdate = () => {
        if (!scrollFrame) scrollFrame = window.requestAnimationFrame(updateFromScroll);
    };

    links.forEach((link) => {
        link.addEventListener('click', () => {
            const targetId = link.hash.slice(1);

            activate(targetId);
            document.body.classList.remove('menu-open');
            navigationScroll = true;
            window.clearTimeout(navigationReleaseTimer);
            navigationReleaseTimer = window.setTimeout(() => {
                navigationScroll = false;
                scheduleScrollUpdate();
            }, 1800);
        });
    });

    window.addEventListener('scroll', scheduleScrollUpdate, { passive: true });
    window.addEventListener('scrollend', () => {
        if (!navigationScroll) return;

        navigationScroll = false;
        window.clearTimeout(navigationReleaseTimer);
        scheduleScrollUpdate();
    }, { passive: true });

    window.addEventListener('hashchange', () => {
        const id = window.location.hash.slice(1) || 'home';

        if (activate(id)) scheduleScrollUpdate();
    });

    const initialId = window.location.hash.slice(1);

    if (!activate(initialId)) activate('home');
    scheduleScrollUpdate();
})();
