const menuButton = document.querySelector('.site-menu-toggle');
const navigation = document.getElementById('site-navigation');
const aboutLink = document.querySelector('[data-nav-about]');
const homeLink = navigation?.querySelector('.site-nav-home');
if (aboutLink && homeLink) {
    const syncCurrentNavigation = () => {
        const onAbout = window.location.hash === '#about';
        if (onAbout) {
            aboutLink.setAttribute('aria-current', 'location');
            homeLink.removeAttribute('aria-current');
        } else {
            aboutLink.removeAttribute('aria-current');
            if (homeLink.dataset.currentOnPage === 'true') {
                homeLink.setAttribute('aria-current', 'page');
            }
        }
    };
    homeLink.dataset.currentOnPage = String(homeLink.getAttribute('aria-current') === 'page');
    syncCurrentNavigation();
    window.addEventListener('hashchange', syncCurrentNavigation);
}

if (menuButton && navigation) {
    menuButton.hidden = false;
    navigation.dataset.collapsible = 'true';
    const closeMenu = () => {
        menuButton.setAttribute('aria-expanded', 'false');
        navigation.dataset.open = 'false';
    };
    menuButton.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        navigation.dataset.open = String(open);
    });
    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a')) closeMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menuButton.focus();
        }
    });
}

const studyUser = document.querySelector('[data-study-user]');
if (studyUser) {
    document.querySelectorAll('[data-session-count]').forEach(element => {
        element.textContent = studyUser.dataset.completedSessions ?? '0';
    });
}

const timer = document.querySelector('[data-pomodoro]');
if (timer) {
    const durations = { focus: 25 * 60, break: 5 * 60 };
    const user = document.querySelector('[data-study-user]').dataset.studyUser;
    const storageKey = `edufocus:timer:${user}`;
    const display = timer.querySelector('[data-timer-display]');
    const start = timer.querySelector('[data-timer-start]');
    const message = timer.querySelector('[data-timer-message]');
    const count = document.querySelector('[data-session-count]');
    const styleDialog = timer.querySelector('[data-style-dialog]');
    const styleForm = timer.querySelector('[data-style-form]');
    const lessonSelect = timer.querySelector('[data-style-lesson]');
    const confirmStyle = timer.querySelector('[data-style-confirm]');
    const readingPane = document.querySelector('[data-study-overlay]');
    const pdfReader = readingPane.querySelector('[data-pdf-reader]');
    const pdfCanvasWrap = readingPane.querySelector('[data-pdf-canvas-wrap]');
    const pdfCanvas = readingPane.querySelector('[data-pdf-canvas]');
    const pagePrevious = readingPane.querySelector('[data-page-previous]');
    const pageNext = readingPane.querySelector('[data-page-next]');
    const pagePosition = readingPane.querySelector('[data-page-position]');
    const pageProgress = readingPane.querySelector('[data-page-progress]');
    const readingTitle = readingPane.querySelector('[data-reading-title]');
    const readingClock = readingPane.querySelector('[data-reading-clock]');
    const readingClose = readingPane.querySelector('[data-reading-close]');
    const readingPrevious = readingPane.querySelector('[data-reading-previous]');
    const readingNext = readingPane.querySelector('[data-reading-next]');
    const readingPosition = readingPane.querySelector('[data-reading-position]');
    const output = readingPane.querySelector('[data-study-material]');
    const styleChoices = new Set(['Visual', 'Flashcards', 'Reading', 'Quiz']);
    const stopWords = new Set([
        'about', 'after', 'again', 'also', 'among', 'because', 'before', 'being', 'between', 'could',
        'does', 'during', 'each', 'from', 'have', 'into', 'more', 'most', 'other', 'over', 'same',
        'should', 'some', 'such', 'than', 'that', 'their', 'them', 'then', 'there', 'these', 'they',
        'this', 'those', 'through', 'under', 'very', 'what', 'when', 'where', 'which', 'while', 'with',
        'would', 'your', 'will', 'were', 'been', 'must', 'can', 'done',
    ]);
    let state = { mode: 'focus', remaining: durations.focus, endAt: null, sessions: 0, learningStyle: null, pendingCompletion: null };
    let quizInterval = null;
    let readingFiles = [];
    let readingFileIndex = 0;
    let readingPageNumber = 1;
    let readingDocument = null;
    let readingLoadingTask = null;
    let readingRenderTask = null;
    let readingFileLoadSequence = 0;
    let readingRenderSequence = 0;
    let pdfJsPromise = null;
    let completionRequestInFlight = false;
    let completionRetryTimeout = null;

    const restore = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey));
            if (!saved || !Object.hasOwn(durations, saved.mode)) return;
            if (!Number.isFinite(saved.remaining) || saved.remaining < 0 || saved.remaining > durations[saved.mode]) return;
            if (!Number.isInteger(saved.sessions) || saved.sessions < 0) return;
            if (saved.endAt !== null && (!Number.isFinite(saved.endAt) || saved.endAt > Date.now() + durations[saved.mode] * 1000)) return;
            if (saved.learningStyle !== null && saved.learningStyle !== undefined && !styleChoices.has(saved.learningStyle)) return;
            state = {
                mode: saved.mode,
                remaining: saved.remaining,
                endAt: saved.endAt,
                sessions: saved.sessions,
                learningStyle: saved.learningStyle ?? null,
                pendingCompletion: typeof saved.pendingCompletion === 'string'
                    && /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iu.test(saved.pendingCompletion)
                    ? saved.pendingCompletion
                    : null,
            };
        } catch { /* The timer also works when browser storage is unavailable. */ }
    };
    const save = () => {
        try { localStorage.setItem(storageKey, JSON.stringify(state)); } catch { /* Keep this session usable. */ }
    };
    const getPdfJs = () => {
        pdfJsPromise ??= Promise.all([
            import('pdfjs-dist'),
            import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
        ]).then(([pdfjs, { default: workerUrl }]) => {
            pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;
            return pdfjs;
        });
        return pdfJsPromise;
    };
    const disposeReadingDocument = async () => {
        readingRenderSequence++;
        readingRenderTask?.cancel();
        readingRenderTask = null;
        const oldTask = readingLoadingTask;
        readingLoadingTask = null;
        readingDocument = null;
        if (oldTask) await oldTask.destroy();
    };
    const syncCompletedSession = async () => {
        if (!state.pendingCompletion || completionRequestInFlight) return;
        completionRequestInFlight = true;
        window.clearTimeout(completionRetryTimeout);
        try {
            const response = await fetch(studyUser.dataset.focusSessionUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ completion_key: state.pendingCompletion }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not save the completed focus session.');
            studyUser.dataset.completedSessions = String(result.completedSessions);
            studyUser.dataset.currentStreak = String(result.currentStreak);
            studyUser.dataset.longestStreak = String(result.longestStreak);
            document.querySelectorAll('[data-session-count]').forEach(element => {
                element.textContent = String(result.completedSessions);
            });
            document.querySelectorAll('[data-current-streak-value]').forEach(element => {
                element.textContent = String(result.currentStreak);
            });
            document.querySelectorAll('[data-current-streak-label]').forEach(element => {
                element.textContent = `${result.currentStreak} ${result.currentStreak === 1 ? 'day' : 'days'} in a row`;
            });
            const streakBadge = document.querySelector('[data-five-day-streak]');
            if (streakBadge) {
                const earned = result.longestStreak >= 5;
                streakBadge.classList.toggle('is-earned', earned);
                streakBadge.querySelector('p').textContent = earned
                    ? 'Earned · You studied five days in a row.'
                    : 'Locked · Study five days in a row to earn this badge.';
            }
            state.pendingCompletion = null;
            save();
            message.textContent = 'Session complete. Your study streak has been saved!';
        } catch {
            message.textContent = 'Session finished, but it could not be saved. Retrying automatically…';
            completionRetryTimeout = window.setTimeout(syncCompletedSession, 10000);
        } finally {
            completionRequestInFlight = false;
        }
    };
    const render = () => {
        if (state.endAt !== null) {
            state.remaining = Math.max(0, Math.ceil((state.endAt - Date.now()) / 1000));
            if (state.remaining === 0) {
                state.endAt = null;
                if (state.mode === 'focus') {
                    state.sessions += 1;
                    state.pendingCompletion ??= crypto.randomUUID();
                    message.textContent = 'Session complete. Saving your study streak…';
                } else {
                    message.textContent = 'Break complete. Ready for your next small step?';
                }
                save();
                syncCompletedSession();
            }
        }
        display.textContent = `${String(Math.floor(state.remaining / 60)).padStart(2, '0')}:${String(state.remaining % 60).padStart(2, '0')}`;
        readingClock.textContent = display.textContent;
        start.textContent = state.endAt !== null ? 'Pause' : state.remaining === 0 ? 'Start again' : state.remaining < durations[state.mode] ? 'Resume' : state.mode === 'focus' ? 'Start Focus Session' : 'Start break';
        count.textContent = studyUser.dataset.completedSessions ?? '0';
        timer.querySelectorAll('[data-timer-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.timerMode === state.mode)));
    };
    const startTimer = () => {
        if (state.endAt !== null) {
            state.endAt = null;
            message.textContent = 'Paused. Pick up where you left off.';
        } else {
            if (state.remaining === 0) state.remaining = durations[state.mode];
            state.endAt = Date.now() + state.remaining * 1000;
            message.textContent = state.mode === 'focus'
                ? `${state.learningStyle} session started. One thing at a time. You’ve got this.`
                : 'Step away. Stretch. Rest your eyes.';
        }
        save(); render();
    };
    const makeElement = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text) element.textContent = text;
        return element;
    };
    const setOutputMessage = (text, isError = false) => {
        void disposeReadingDocument();
        pdfReader.hidden = true;
        output.hidden = false;
        readingPane.hidden = false;
        output.replaceChildren();
        const status = makeElement('p', isError ? 'study-material-error' : 'study-material-status', text);
        status.setAttribute('role', isError ? 'alert' : 'status');
        output.append(status);
    };
    const showReadingFiles = async (title, files) => {
        readingTitle.textContent = title;
        output.hidden = true;
        pdfReader.hidden = false;
        readingFiles = files;
        readingFileIndex = 0;
        readingPageNumber = 1;
        readingPane.hidden = false;
        readingClose.focus();
        await updateReadingFile();
    };
    const updateReadingFile = async () => {
        const loadSequence = ++readingFileLoadSequence;
        const file = readingFiles[readingFileIndex];
        readingPosition.textContent = readingFiles.length > 1 ? `PDF ${readingFileIndex + 1} of ${readingFiles.length}` : '';
        readingPrevious.disabled = readingFileIndex === 0;
        readingNext.disabled = readingFileIndex >= readingFiles.length - 1;
        readingTitle.textContent = `${file.title} · Reading`;
        pageProgress.textContent = 'Loading PDF…';
        pagePrevious.disabled = true;
        pageNext.disabled = true;
        try {
            await disposeReadingDocument();
            const response = await fetch(file.url, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('Could not open this lesson PDF. Try opening it from your library.');
            const pdfjs = await getPdfJs();
            if (loadSequence !== readingFileLoadSequence) return;
            const loadingTask = pdfjs.getDocument({ data: new Uint8Array(await response.arrayBuffer()) });
            readingLoadingTask = loadingTask;
            const pdf = await loadingTask.promise;
            if (loadSequence !== readingFileLoadSequence || readingFiles[readingFileIndex] !== file) {
                if (readingLoadingTask === loadingTask) readingLoadingTask = null;
                await loadingTask.destroy();
                return;
            }
            readingDocument = pdf;
            readingPageNumber = Math.min(readingPageNumber, pdf.numPages);
            await renderReadingPage();
        } catch (error) {
            if (loadSequence !== readingFileLoadSequence) return;
            pageProgress.textContent = error instanceof Error ? error.message : 'Could not open this lesson PDF.';
            pageProgress.setAttribute('role', 'alert');
        }
    };
    readingPrevious.addEventListener('click', () => {
        if (readingFileIndex > 0) {
            readingFileIndex -= 1;
            readingPageNumber = 1;
            void updateReadingFile();
        }
    });
    readingNext.addEventListener('click', () => {
        if (readingFileIndex < readingFiles.length - 1) {
            readingFileIndex += 1;
            readingPageNumber = 1;
            void updateReadingFile();
        }
    });
    pagePrevious.addEventListener('click', () => {
        if (readingPageNumber > 1) {
            readingPageNumber--;
            void renderReadingPage();
        }
    });
    pageNext.addEventListener('click', () => {
        if (readingDocument && readingPageNumber < readingDocument.numPages) {
            readingPageNumber++;
            void renderReadingPage();
        }
    });
    readingClose.addEventListener('click', () => {
        window.clearInterval(quizInterval);
        quizInterval = null;
        readingFiles = [];
        readingFileIndex = 0;
        readingPageNumber = 1;
        void disposeReadingDocument();
        pdfReader.hidden = true;
        output.replaceChildren();
        output.hidden = true;
        readingPane.hidden = true;
        start.focus();
    });
    const renderReadingPage = async () => {
        if (!readingDocument) return;
        const renderSequence = ++readingRenderSequence;
        try {
            const page = await readingDocument.getPage(readingPageNumber);
            if (renderSequence !== readingRenderSequence || !readingDocument) return;
            const baseViewport = page.getViewport({ scale: 1 });
            const availableWidth = Math.max(pdfCanvasWrap.clientWidth - 32, 1);
            const availableHeight = Math.max(pdfCanvasWrap.clientHeight - 32, 1);
            const scale = Math.min(availableWidth / baseViewport.width, availableHeight / baseViewport.height);
            const viewport = page.getViewport({ scale });
            const pixelRatio = window.devicePixelRatio || 1;
            pdfCanvas.width = Math.floor(viewport.width * pixelRatio);
            pdfCanvas.height = Math.floor(viewport.height * pixelRatio);
            pdfCanvas.style.width = `${viewport.width}px`;
            pdfCanvas.style.height = `${viewport.height}px`;
            const context = pdfCanvas.getContext('2d');
            if (!context) throw new Error('This browser could not display the PDF page.');
            context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
            readingRenderTask?.cancel();
            const renderTask = page.render({ canvasContext: context, viewport });
            readingRenderTask = renderTask;
            try {
                await renderTask.promise;
            } finally {
                if (readingRenderTask === renderTask) readingRenderTask = null;
            }
        } catch (error) {
            if (renderSequence !== readingRenderSequence) return;
            pageProgress.textContent = error instanceof Error ? error.message : 'Could not render this PDF page.';
            pageProgress.setAttribute('role', 'alert');
            return;
        }
        if (renderSequence !== readingRenderSequence) return;
        pagePosition.textContent = `Page ${readingPageNumber} of ${readingDocument.numPages}`;
        pagePrevious.disabled = readingPageNumber <= 1;
        pageNext.disabled = readingPageNumber >= readingDocument.numPages;
        pageProgress.textContent = 'Saving page progress…';
        pageProgress.setAttribute('role', 'status');
        void savePageProgress(readingFiles[readingFileIndex], readingPageNumber, readingDocument.numPages);
    };
    const savePageProgress = async (file, pageNumber, totalPages) => {
        try {
            const response = await fetch(file.progressUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ page_number: pageNumber, total_pages: totalPages }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not save this page’s progress.');
            if (readingFiles[readingFileIndex] === file && readingPageNumber === pageNumber) {
                pageProgress.textContent = `Lesson progress: ${result.progress}%`;
            }
            const progressBar = document.querySelector(`[data-lesson-progress="${result.lessonId}"]`);
            const progressLabel = document.querySelector(`[data-lesson-progress-label="${result.lessonId}"]`);
            if (progressBar) progressBar.value = result.progress;
            if (progressLabel) progressLabel.textContent = `${result.progress}%`;
        } catch (error) {
            if (readingFiles[readingFileIndex] === file && readingPageNumber === pageNumber) {
                pageProgress.textContent = error instanceof Error
                    ? `${error.message} Reopen this page to retry.`
                    : 'Could not save progress. Reopen this page to retry.';
                pageProgress.setAttribute('role', 'alert');
            }
        }
    };
    window.addEventListener('resize', () => {
        if (!pdfReader.hidden && readingDocument) void renderReadingPage();
    });
    const extractPdf = async url => {
        const { getDocument } = await getPdfJs();
        const response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('Could not open this lesson PDF. Try opening it from your library.');
        const documentTask = getDocument({ data: new Uint8Array(await response.arrayBuffer()) });
        const pdf = await documentTask.promise;
        try {
            if (pdf.numPages > 100) {
                throw new Error('Visual, Flashcards, and Quiz support PDFs up to 100 pages. Reading can open the full PDF.');
            }
            const pageTexts = [];
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                const page = await pdf.getPage(pageNumber);
                const content = await page.getTextContent();
                pageTexts.push(content.items.map(item => 'str' in item ? item.str : '').join(' '));
            }
            const text = pageTexts.join('\n').replace(/[ \t]+/gu, ' ').trim();
            if (text.length < 80) {
                throw new Error('No readable text was found in this PDF. Scanned/image-only PDFs are not supported yet.');
            }
            return text;
        } finally {
            await documentTask.destroy();
        }
    };
    const analyzeText = text => {
        const sentences = text.split(/(?<=[.!?])\s+|\n+/u).map(sentence => sentence.trim()).filter(sentence => sentence.length >= 35);
        const frequencies = new Map();
        for (const word of text.match(/\p{L}[\p{L}\p{N}'’-]{3,}/gu) ?? []) {
            const normalized = word.toLocaleLowerCase();
            if (!stopWords.has(normalized)) frequencies.set(normalized, (frequencies.get(normalized) ?? 0) + 1);
        }
        const terms = [...frequencies.keys()].sort((left, right) => frequencies.get(right) - frequencies.get(left) || right.length - left.length);
        return { sentences, terms };
    };
    const escapeRegExp = value => value.replace(/[.*+?^${}()|[\]\\]/gu, '\\$&');
    const cloze = (sentence, term) => sentence.replace(new RegExp(`\\b${escapeRegExp(term)}\\b`, 'iu'), '_____');
    const shuffled = items => {
        const copy = [...items];
        for (let index = copy.length - 1; index > 0; index -= 1) {
            const swapIndex = Math.floor(Math.random() * (index + 1));
            [copy[index], copy[swapIndex]] = [copy[swapIndex], copy[index]];
        }
        return copy;
    };
    const renderVisual = (title, sentences, terms) => {
        readingTitle.textContent = `Visual · ${title}`;
        output.replaceChildren(makeElement('p', 'site-eyebrow', 'VISUAL STUDY GUIDE'), makeElement('h2', '', title));
        const cards = makeElement('div', 'study-visual-cards');
        const ideas = sentences.filter(sentence => terms.some(term => sentence.toLocaleLowerCase().includes(term))).slice(0, 6);
        ideas.forEach((sentence, index) => {
            const card = makeElement('article', 'study-visual-card');
            card.append(makeElement('span', 'site-card-index', `0${index + 1}`), makeElement('p', '', sentence));
            cards.append(card);
        });
        output.append(cards, makeElement('h3', '', 'Key concepts'));
        const map = makeElement('div', 'study-concept-map');
        map.append(makeElement('strong', 'study-concept-center', title));
        terms.slice(0, 6).forEach(term => map.append(makeElement('span', 'study-concept-node', term)));
        output.append(map);
    };
    const renderFlashcards = (sentences, terms) => {
        const cards = terms.slice(0, 10).map(term => {
            const sentence = sentences.find(item => new RegExp(`\\b${escapeRegExp(term)}\\b`, 'iu').test(item));
            return sentence ? { term, sentence } : null;
        }).filter(Boolean);
        if (!cards.length) throw new Error('There is not enough readable text to make flashcards from this PDF.');
        readingTitle.textContent = 'Flashcards';
        output.replaceChildren(makeElement('p', 'site-eyebrow', 'FLASHCARDS FROM YOUR PDF'), makeElement('h2', '', 'Review the key ideas'));
        const grid = makeElement('div', 'study-flashcard-grid');
        cards.forEach(({ term, sentence }, index) => {
            const card = makeElement('article', 'study-flashcard');
            card.append(makeElement('span', 'site-card-index', `0${index + 1}`), makeElement('p', 'study-flashcard-front', cloze(sentence, term)));
            const answer = makeElement('p', 'study-flashcard-answer', `Answer: ${term}`);
            answer.hidden = true;
            const reveal = makeElement('button', 'site-button site-button--outline site-button--small', 'Show answer');
            reveal.type = 'button';
            reveal.addEventListener('click', () => {
                answer.hidden = !answer.hidden;
                reveal.textContent = answer.hidden ? 'Show answer' : 'Hide answer';
            });
            card.append(answer, reveal);
            grid.append(card);
        });
        output.append(grid);
    };
    const renderQuiz = (sentences, terms) => {
        const questions = shuffled(terms.map(term => {
            const sentence = sentences.find(item => new RegExp(`\\b${escapeRegExp(term)}\\b`, 'iu').test(item));
            return sentence ? { term, prompt: cloze(sentence, term) } : null;
        }).filter(Boolean)).slice(0, 10);
        if (terms.length < 4 || !questions.length) {
            throw new Error('There is not enough readable text to generate a quiz from this PDF.');
        }
        readingTitle.textContent = 'Quiz';
        output.replaceChildren(makeElement('p', 'site-eyebrow', 'QUICK CHECK'), makeElement('h2', '', 'Quiz from your PDF'));
        const progress = makeElement('p', 'study-quiz-progress');
        const clock = makeElement('p', 'study-quiz-clock');
        clock.setAttribute('role', 'timer');
        const prompt = makeElement('p', 'study-quiz-question');
        const choices = makeElement('div', 'study-quiz-choices');
        const next = makeElement('button', 'site-button', 'Next question');
        next.type = 'button';
        next.hidden = true;
        output.append(progress, clock, prompt, choices, next);
        let index = 0;
        let seconds = 15;
        let score = 0;
        const showQuestion = () => {
            window.clearInterval(quizInterval);
            if (index >= questions.length) {
                quizInterval = null;
                output.replaceChildren(
                    makeElement('p', 'site-eyebrow', 'QUIZ COMPLETE'),
                    makeElement('h2', '', `Score: ${score} / ${questions.length}`),
                    makeElement('p', 'study-quiz-result', 'You reached the end of this quiz.'),
                );
                return;
            }
            const current = questions[index];
            seconds = 15;
            progress.textContent = `Question ${index + 1} of ${questions.length}`;
            clock.textContent = `${seconds} seconds`;
            prompt.textContent = current.prompt;
            next.hidden = true;
            choices.replaceChildren();
            shuffled([current.term, ...shuffled(terms.filter(term => term !== current.term)).slice(0, 3)]).forEach(option => {
                const button = makeElement('button', 'study-quiz-choice', option);
                button.type = 'button';
                button.addEventListener('click', () => {
                    window.clearInterval(quizInterval);
                    quizInterval = null;
                    if (option === current.term) score += 1;
                    choices.querySelectorAll('button').forEach(choice => {
                        choice.disabled = true;
                        if (choice.textContent === current.term) choice.classList.add('is-correct');
                    });
                    if (option !== current.term) button.classList.add('is-incorrect');
                    next.hidden = false;
                });
                choices.append(button);
            });
            quizInterval = window.setInterval(() => {
                seconds -= 1;
                clock.textContent = `${seconds} seconds`;
                if (seconds <= 0) {
                    window.clearInterval(quizInterval);
                    quizInterval = null;
                    choices.querySelectorAll('button').forEach(choice => {
                        choice.disabled = true;
                        if (choice.textContent === current.term) choice.classList.add('is-correct');
                    });
                    next.hidden = false;
                }
            }, 1000);
        };
        next.addEventListener('click', () => { index += 1; showQuestion(); });
        showQuestion();
    };
    start.addEventListener('click', () => {
        render();
        if (state.endAt !== null) {
            startTimer();
            return;
        }
        if (state.mode === 'focus' && (state.remaining === durations.focus || state.remaining === 0 || !state.learningStyle)) {
            styleDialog.showModal();
            return;
        }
        startTimer();
    });
    styleForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (event.submitter?.value !== 'start') {
            styleDialog.close('cancel');
            return;
        }
        const selectedStyle = styleForm.querySelector('input[name="learning-style"]:checked')?.value;
        const selectedLesson = lessonSelect?.selectedOptions[0];
        if (!selectedStyle || !styleChoices.has(selectedStyle) || !selectedLesson) return;
        const title = selectedLesson.dataset.title;
        let selectedFiles;
        try {
            selectedFiles = JSON.parse(selectedLesson.dataset.files ?? '[]');
        } catch {
            setOutputMessage('The selected PDF or deck could not be loaded. Refresh the page and try again.', true);
            return;
        }
        if (!Array.isArray(selectedFiles) || selectedFiles.length === 0 || selectedFiles.some(file => typeof file.title !== 'string' || typeof file.url !== 'string')) {
            setOutputMessage('The selected PDF or deck has no available files. Check your study deck in My Lessons.', true);
            return;
        }
        state.learningStyle = selectedStyle;
        styleDialog.close('start');
        if (selectedStyle === 'Reading') {
            showReadingFiles(title, selectedFiles);
            startTimer();
            return;
        }
        setOutputMessage(`Preparing ${selectedStyle.toLocaleLowerCase()} material from “${title}”…`);
        confirmStyle.disabled = true;
        try {
            const textParts = [];
            for (const file of selectedFiles) {
                textParts.push(await extractPdf(file.url));
            }
            const text = textParts.join('\n');
            const { sentences, terms } = analyzeText(text);
            if (!sentences.length || terms.length < 2) {
                throw new Error('There is not enough readable text in this PDF to make study material.');
            }
            if (selectedStyle === 'Visual') renderVisual(title, sentences, terms);
            if (selectedStyle === 'Flashcards') renderFlashcards(sentences, terms);
            if (selectedStyle === 'Quiz') renderQuiz(sentences, terms);
            startTimer();
        } catch (error) {
            setOutputMessage(error instanceof Error ? error.message : 'Could not prepare study material from this PDF.', true);
        } finally {
            confirmStyle.disabled = false;
        }
    });
    timer.querySelector('[data-timer-reset]').addEventListener('click', () => {
        state.remaining = durations[state.mode]; state.endAt = null;
        message.textContent = 'A fresh timer. Start when you’re ready.';
        save(); render();
    });
    timer.querySelectorAll('[data-timer-mode]').forEach(button => button.addEventListener('click', () => {
        if (state.mode === button.dataset.timerMode) return;
        state.mode = button.dataset.timerMode;
        state.remaining = durations[state.mode]; state.endAt = null;
        message.textContent = state.mode === 'focus'
            ? state.learningStyle ? `${state.learningStyle} session selected. Start when you’re ready.` : 'One thing at a time. You’ve got this.'
            : 'Make a little time to recharge.';
        save(); render();
    }));
    window.addEventListener('storage', event => { if (event.key === storageKey) { restore(); render(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) render(); });
    restore(); render(); syncCompletedSession();
    window.addEventListener('online', syncCompletedSession);
    window.setInterval(render, 250);
}
