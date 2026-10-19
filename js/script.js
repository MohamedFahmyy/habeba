document.addEventListener("DOMContentLoaded", () => {
    
    // Custom Cursor Glow
    const glow = document.querySelector('.mouse-glow');
    document.addEventListener('mousemove', (e) => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });

    // Remove Loader
    setTimeout(() => {
        const loader = document.getElementById('loader');
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 1000);
        
        // Start initial animations after load
        initAnimations();
    }, 2000);

    // Particles Generation
    const particlesContainer = document.getElementById('particles');
    const particleCount = 30;
    
    for (let i = 0; i < particleCount; i++) {
        let particle = document.createElement('div');
        // Randomly choose between circle and heart
        if(Math.random() > 0.7) {
            particle.classList.add('heart-particle');
            particle.innerHTML = '<i class="fas fa-heart"></i>';
        } else {
            particle.classList.add('particle');
            particle.style.width = Math.random() * 5 + 2 + 'px';
            particle.style.height = particle.style.width;
        }
        
        particle.style.left = Math.random() * 100 + 'vw';
        particle.style.animationDuration = Math.random() * 10 + 10 + 's';
        particle.style.animationDelay = Math.random() * 10 + 's';
        
        particlesContainer.appendChild(particle);
    }

    // Music Player Logic (YouTube API)
    const musicToggle = document.getElementById('music-toggle');
    let isPlaying = false;

    musicToggle.addEventListener('click', () => {
        if(typeof ytPlayer !== 'undefined' && ytPlayer.playVideo) {
            if(isPlaying) {
                ytPlayer.pauseVideo();
                musicToggle.innerHTML = '<i class="fas fa-music"></i> Our Song';
                musicToggle.style.boxShadow = 'none';
            } else {
                ytPlayer.setVolume(50);
                ytPlayer.playVideo();
                musicToggle.innerHTML = '<i class="fas fa-pause"></i> Playing...';
                musicToggle.style.boxShadow = '0 0 15px rgba(255, 105, 180, 0.8)';
            }
            isPlaying = !isPlaying;
        }
    });

    // GSAP ScrollTrigger Animations
    gsap.registerPlugin(ScrollTrigger);

    function initAnimations() {
        // Hero Section
        gsap.from('.hero-title', { opacity: 0, y: 50, duration: 1.5, ease: "power3.out" });
        gsap.from('.hero-subtitle', { opacity: 0, y: 30, duration: 1.5, delay: 0.5, ease: "power3.out" });
        gsap.from('.btn-start', { opacity: 0, scale: 0.8, duration: 1, delay: 1, ease: "back.out(1.7)" });

        // Timeline
        const timelineCards = gsap.utils.toArray('.timeline-card');
        timelineCards.forEach(card => {
            gsap.to(card, {
                scrollTrigger: {
                    trigger: card,
                    start: "top 80%",
                    toggleActions: "play none none reverse"
                },
                opacity: 1,
                y: 0,
                duration: 1,
                ease: "power2.out"
            });
        });

        // Counters
        ScrollTrigger.create({
            trigger: "#counters",
            start: "top 80%",
            onEnter: () => {
                const counters = document.querySelectorAll('.counter-num');
                counters.forEach(counter => {
                    const target = +counter.getAttribute('data-target');
                    const obj = { val: 0 };
                    gsap.to(obj, {
                        val: target,
                        duration: 2.5,
                        ease: "power1.inOut",
                        onUpdate: function() {
                            counter.innerHTML = '+' + Math.round(obj.val);
                        }
                    });
                });
            },
            once: true
        });

        // Gallery
        gsap.from('.gallery-item', {
            scrollTrigger: {
                trigger: ".gallery",
                start: "top 80%",
            },
            opacity: 0,
            y: 50,
            stagger: 0.2,
            duration: 1,
            ease: "power2.out"
        });

        // Typewriter Effect for Love Letter
        const typingText = document.querySelector('.typing-text');
        if(typingText) {
            const textToType = typingText.getAttribute('data-text');
            typingText.innerHTML = '';
            let i = 0;
            
            ScrollTrigger.create({
                trigger: ".love-letter-container",
                start: "top 70%",
                onEnter: () => {
                    function type() {
                        if(i < textToType.length) {
                            if(textToType.charAt(i) === '<') {
                                // skip to end of tag
                                let tag = "";
                                while(textToType.charAt(i) !== '>') {
                                    tag += textToType.charAt(i);
                                    i++;
                                }
                                tag += '>';
                                typingText.innerHTML += tag;
                            } else {
                                typingText.innerHTML += textToType.charAt(i);
                            }
                            i++;
                            setTimeout(type, 50);
                        }
                    }
                    type();
                },
                once: true
            });
        }
    }

    // Lightbox Logic
    const galleryItems = document.querySelectorAll('.gallery-item img');
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <i class="fas fa-times lightbox-close"></i>
        <img src="" alt="Lightbox Image">
    `;
    document.body.appendChild(lightbox);

    const lightboxImg = lightbox.querySelector('img');
    const lightboxClose = lightbox.querySelector('.lightbox-close');

    galleryItems.forEach(item => {
        item.addEventListener('click', () => {
            lightboxImg.src = item.src;
            lightbox.classList.add('active');
        });
    });

    lightboxClose.addEventListener('click', () => {
        lightbox.classList.remove('active');
    });
    lightbox.addEventListener('click', (e) => {
        if(e.target === lightbox) {
            lightbox.classList.remove('active');
        }
    });

});
