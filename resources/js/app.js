import "./bootstrap";

let revealObserver;
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
        const target = document.getElementById("profile-data-diri");

        if (!target) {
            return;
        }

        const extraOffset = 56;
        const targetTop =
            target.getBoundingClientRect().top + window.scrollY + extraOffset;

        window.scrollTo({ top: targetTop, behavior: "smooth" });
    });
};

const initMotion = () => {
    initRupiahInputs();
    applyStagger();
    prepareMotionPage();
    revealElements();
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
document.addEventListener("livewire:initialized", () => {
    window.Livewire?.hook("morphed", ({ el }) => {
        initRupiahInputs(el);
        initMotion();
    });
});
