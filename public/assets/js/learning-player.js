(function () {
    'use strict';

    const root = document.querySelector('[data-learning-player]');
    if (!root) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const progressUrl = root.dataset.progressUrl;
    const completeUrl = root.dataset.completeUrl;
    const initialPosition = parseInt(root.dataset.lastPosition || '0', 10);
    const alreadyCompleted = root.dataset.completed === '1';

    const video = root.querySelector('video');
    const completeBtn = root.querySelector('[data-complete-btn]');
    const progressBar = document.querySelector('[data-course-progress-bar]');
    const progressLabel = document.querySelector('[data-course-progress-label]');
    const sidebarRow = document.querySelector(`[data-material-row="${root.dataset.materialId}"]`);

    async function post(url, body = {}) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            console.warn('learning-player:', e);
            return null;
        }
    }

    function markLocalCompleted() {
        if (!completeBtn) return;
        completeBtn.disabled = true;
        completeBtn.classList.remove('btn-success');
        completeBtn.classList.add('btn-success');
        completeBtn.innerHTML = '<i class="isax isax-tick-circle me-1"></i> Completed';

        if (sidebarRow) {
            const icon = sidebarRow.querySelector('[data-material-icon]');
            if (icon) {
                icon.className = 'isax isax-tick-circle5 text-success fs-18';
            }
        }
    }

    function updateCourseProgress(percentage, courseCompleted) {
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            if (courseCompleted) {
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-success');
            }
        }
        if (progressLabel) {
            progressLabel.textContent = percentage + '%';
        }
    }

    async function completeMaterial() {
        if (!completeUrl) return;
        const data = await post(completeUrl);
        if (data && data.ok) {
            markLocalCompleted();
            updateCourseProgress(data.progress_percentage, data.course_completed);
        }
    }

    // --- Video tracking ---
    if (video) {
        let lastReportedAt = 0;
        let accumulatedTime = 0;
        let lastTime = 0;
        let autoCompleted = alreadyCompleted;

        video.addEventListener('loadedmetadata', () => {
            if (initialPosition > 0 && initialPosition < video.duration - 2) {
                video.currentTime = initialPosition;
            }
        });

        video.addEventListener('timeupdate', () => {
            const now = video.currentTime;

            // Accumulate only forward, small-step time (avoid counting seeks)
            const delta = now - lastTime;
            if (delta > 0 && delta < 2) {
                accumulatedTime += delta;
            }
            lastTime = now;

            // Throttle reporting to once per 10s of wall time
            const nowMs = Date.now();
            if (nowMs - lastReportedAt >= 10000) {
                lastReportedAt = nowMs;
                const timeSpentDelta = Math.round(accumulatedTime);
                accumulatedTime = 0;
                post(progressUrl, {
                    last_position: Math.floor(now),
                    time_spent_delta: timeSpentDelta,
                });
            }

            // Auto-complete at >=90% watched
            if (!autoCompleted && video.duration > 0 && now / video.duration >= 0.9) {
                autoCompleted = true;
                completeMaterial();
            }
        });

        video.addEventListener('ended', () => {
            if (!autoCompleted) {
                autoCompleted = true;
                completeMaterial();
            }
        });

        // Final flush on unload
        window.addEventListener('beforeunload', () => {
            if (accumulatedTime > 0 || video.currentTime > 0) {
                navigator.sendBeacon && navigator.sendBeacon(
                    progressUrl,
                    new Blob([JSON.stringify({
                        last_position: Math.floor(video.currentTime),
                        time_spent_delta: Math.round(accumulatedTime),
                        _token: csrfToken,
                    })], { type: 'application/json' })
                );
            }
        });
    }

    // --- Mark-as-complete button (docs + manual for videos) ---
    if (completeBtn && !alreadyCompleted) {
        completeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            completeBtn.disabled = true;
            completeMaterial();
        });
    }
})();
