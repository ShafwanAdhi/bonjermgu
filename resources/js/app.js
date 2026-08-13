import "./bootstrap";

let revealObserver;
let adminScrollObserver;
let scrollProgressBound = false;
let progressFrame;

const prefersReducedMotion = () =>
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

const applyStagger = () => {
    document.querySelectorAll("[data-motion-stagger]").forEach((group) => {
        const step = Number(group.dataset.motionStep ?? 55);

        [...group.children].forEach((child, index) => {
            if (!child.hasAttribute("data-reveal")) {
                child.setAttribute("data-reveal", "item");
            }

            if (!child.style.getPropertyValue("--reveal-delay")) {
                child.style.setProperty(
                    "--reveal-delay",
                    `${Math.min(index * step, 420)}ms`,
                );
            }
        });
    });
};

const revealElements = () => {
    const targets = [
        ...document.querySelectorAll("[data-reveal]:not(.is-visible)"),
    ];

    document.documentElement.classList.add("reveal-ready");

    if (!targets.length) {
        return;
    }

    if (prefersReducedMotion() || !("IntersectionObserver" in window)) {
        targets.forEach((target) => target.classList.add("is-visible"));

        return;
    }

    revealObserver ??= new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("is-visible");
                revealObserver.unobserve(entry.target);
            });
        },
        {
            rootMargin: "0px 0px -8% 0px",
            threshold: 0.12,
        },
    );

    targets.forEach((target) => revealObserver.observe(target));
};

const updateScrollProgress = () => {
    progressFrame = null;

    const progress = document.querySelector("[data-scroll-progress]");

    if (!progress) {
        return;
    }

    const scrollable =
        document.documentElement.scrollHeight - window.innerHeight;
    const value =
        scrollable <= 0 ? 0 : Math.min(window.scrollY / scrollable, 1);

    progress.style.setProperty("--scroll-progress", value.toString());
};

const queueScrollProgress = () => {
    if (progressFrame) {
        return;
    }

    progressFrame = requestAnimationFrame(updateScrollProgress);
};

const setupScrollProgress = () => {
    if (!scrollProgressBound) {
        window.addEventListener("scroll", queueScrollProgress, {
            passive: true,
        });
        window.addEventListener("resize", queueScrollProgress);
        scrollProgressBound = true;
    }

    queueScrollProgress();
};

const prepareMotionPage = () => {
    document.querySelectorAll("[data-motion-page]").forEach((page) => {
        page.classList.add("motion-page-ready");
    });
};

const adminMotionTargets = [
    ".band > .mb-md",
    ".band > .mb-xl",
    ".band > .mb-5",
    ".band > .mb-1\\.5",
    ".band > [aria-label='Navigasi modul'] > a",
    ".band > .grid > *",
    ".band > .rounded-lg",
    ".band > .rounded-md",
    ".band > form",
    ".band > section",
    ".band > article",
    ".band > [data-admin-animate]",
    ".band table tbody tr",
    ".band .overflow-x-auto",
    "[data-admin-motion-page] > .mx-auto > .mb-md",
    "[data-admin-motion-page] > .mx-auto > .mb-xl",
    "[data-admin-motion-page] > .mx-auto > .rounded-lg",
    "[data-admin-motion-page] > .mx-auto > form",
];

const isAdminModuleLeafPage = () => {
    const path = window.location.pathname;

    return [
        "/configuration/",
        "/master/",
        "/accounts/",
        "/lending/",
    ].some((prefix) => path.startsWith(prefix));
};

const prepareAdminDashboardScrollMotion = () => {
    if (
        document.body?.dataset.role !== "admin" ||
        window.location.pathname !== "/dashboard"
    ) {
        return;
    }

    const targets = [
        ...document.querySelectorAll(
            "[data-admin-scroll]:not(.admin-scroll-visible)",
        ),
    ];

    document.documentElement.classList.add("admin-scroll-ready");

    if (!targets.length) {
        return;
    }

    if (prefersReducedMotion() || !("IntersectionObserver" in window)) {
        targets.forEach((target) =>
            target.classList.add("admin-scroll-visible"),
        );

        return;
    }

    adminScrollObserver ??= new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("admin-scroll-visible");
                adminScrollObserver.unobserve(entry.target);
            });
        },
        {
            rootMargin: "0px 0px -14% 0px",
            threshold: 0.16,
        },
    );

    targets.forEach((target, index) => {
        if (!target.style.getPropertyValue("--admin-scroll-delay")) {
            target.style.setProperty(
                "--admin-scroll-delay",
                `${Math.min(index * 80, 240)}ms`,
            );
        }

        adminScrollObserver.observe(target);
    });
};

const prepareAdminMotion = () => {
    if (document.body?.dataset.role !== "admin") {
        return;
    }

    const page = document.querySelector("[data-admin-motion-page]");

    page?.classList.remove("admin-motion-ready");

    if (isAdminModuleLeafPage()) {
        document.documentElement.classList.remove("admin-motion-prep");
        page?.classList.add("admin-motion-ready");
        document.querySelectorAll("[data-admin-animate]").forEach((target) => {
            target.removeAttribute("data-admin-animate");
            target.classList.remove("admin-motion-in");
            target.style.removeProperty("--admin-motion-delay");
        });

        return;
    }

    document.documentElement.classList.add("admin-motion-prep");

    const targets = [
        ...new Set(
            adminMotionTargets.flatMap((selector) => [
                ...document.querySelectorAll(selector),
            ]),
        ),
    ].filter((target) => !target.closest("[data-no-admin-motion]"));

    targets.forEach((target, index) => {
        target.setAttribute("data-admin-animate", "");
        target.style.setProperty(
            "--admin-motion-delay",
            `${Math.min(index * 34, 360)}ms`,
        );
    });

    document
        .querySelectorAll(
            ".band table tbody tr:not([data-motion-row]), .band [role='menuitem']:not([data-motion-action])",
        )
        .forEach((target) => target.setAttribute("data-motion-row", ""));

    requestAnimationFrame(() => {
        page?.classList.add("admin-motion-ready");
        targets.forEach((target) => target.classList.add("admin-motion-in"));
        prepareAdminDashboardScrollMotion();
        window.setTimeout(() => {
            document.documentElement.classList.remove("admin-motion-prep");
        }, 760);
    });
};

const rupiahDigits = (value) => value.replace(/\D/g, "");

const formatRupiah = (value) => {
    const digits = rupiahDigits(value);

    if (digits === "") {
        return "";
    }

    return `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
};

const initRupiahInputs = (root = document) => {
    root.querySelectorAll("[data-rupiah-input]").forEach((input) => {
        input.value = formatRupiah(input.value);

        if (input.dataset.rupiahReady === "true") {
            return;
        }

        input.dataset.rupiahReady = "true";

        input.addEventListener("input", () => {
            input.value = formatRupiah(input.value);
        });

        input.addEventListener("blur", () => {
            input.value = formatRupiah(input.value);
        });
    });
};

const scrollProfileFormIntoView = () => {
    requestAnimationFrame(() => {
        const target =
            document.querySelector("[data-profile-focus-target]") ??
            document.getElementById("profile-data-diri");

        if (!target) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const targetTop =
            rect.top + window.scrollY - window.innerHeight / 2 + rect.height / 2;

        window.scrollTo({ top: Math.max(targetTop, 0), behavior: "smooth" });
    });
};

const scrollProfileTopIntoView = () => {
    requestAnimationFrame(() => {
        window.scrollTo({
            top: 0,
            behavior: prefersReducedMotion() ? "auto" : "smooth",
        });
    });
};

const scrollProductEditorIntoView = () => {
    if (!window.matchMedia("(max-width: 1279px)").matches) {
        return;
    }

    window.setTimeout(() => {
        const target = document.querySelector("[data-product-editor-target]");

        if (!target) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const offset = Math.min(window.innerHeight * 0.14, 96);
        const targetTop = rect.top + window.scrollY - offset;

        window.scrollTo({ top: Math.max(targetTop, 0), behavior: "smooth" });
    }, 180);
};

const scrollMasterPanelIntoView = () => {
    window.setTimeout(() => {
        const target = document.querySelector("[data-master-active-panel]");

        if (!target) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const offset = Math.min(window.innerHeight * 0.16, 112);
        const targetTop = rect.top + window.scrollY - offset;

        window.scrollTo({
            top: Math.max(targetTop, 0),
            behavior: prefersReducedMotion() ? "auto" : "smooth",
        });
    }, 180);
};

const scrollMasterStepIntoView = (event) => {
    window.setTimeout(() => {
        const step = event.detail?.target;
        const target = step
            ? document.querySelector(`[data-master-step="${step}"]`)
            : document.querySelector('[data-master-attention="true"]');

        if (!target) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const offset = Math.min(window.innerHeight * 0.18, 128);
        const targetTop = rect.top + window.scrollY - offset;

        window.scrollTo({
            top: Math.max(targetTop, 0),
            behavior: prefersReducedMotion() ? "auto" : "smooth",
        });
    }, 180);
};

const initMotion = () => {
    initRupiahInputs();
    applyStagger();
    prepareMotionPage();
    if (document.body?.dataset.role === "admin") {
        prepareAdminMotion();
        prepareAdminDashboardScrollMotion();
    } else {
        revealElements();
    }
    setupScrollProgress();
};

window.addEventListener("DOMContentLoaded", initMotion);
document.addEventListener("livewire:navigating", () => {
    document.documentElement.classList.add("route-transitioning");
});
document.addEventListener("livewire:navigated", () => {
    requestAnimationFrame(() => {
        document.documentElement.classList.remove("route-transitioning");
        initMotion();
    });
});
window.addEventListener("profile-editing", scrollProfileFormIntoView);
window.addEventListener("profile-password-updated", scrollProfileTopIntoView);
window.addEventListener("configuration-product-selected", scrollProductEditorIntoView);
window.addEventListener("master-panel-opened", scrollMasterPanelIntoView);
window.addEventListener("master-step-attention", scrollMasterStepIntoView);
document.addEventListener("livewire:initialized", () => {
    window.Livewire?.hook("morphed", ({ el }) => {
        initRupiahInputs(el);
        initMotion();
    });
});
