document.addEventListener('DOMContentLoaded', function () {
    if (typeof patientDataForAI === 'undefined') {
        console.error("patientDataForAI not available. AI recommendations cannot be fetched.");
        return;
    }

    const overviewText = document.getElementById('overviewText');
    const riskList = document.getElementById('riskList');
    const precautionList = document.getElementById('precautionList');
    const refreshButton = document.getElementById('refreshRecommendations');
    const recommendationsAside = document.querySelector('aside.recommendations');

    // Display initial loading state
    function showLoading() {
        if (overviewText) overviewText.innerHTML = '<span class="loading-spinner">Generating summary...</span>';
        if (riskList) riskList.innerHTML = '<li><span class="loading-spinner">Analyzing risks...</span></li>';
        if (precautionList) precautionList.innerHTML = '<li><span class="loading-spinner">Generating precautions...</span></li>';
        if (refreshButton) {
            refreshButton.style.pointerEvents = 'none';
            refreshButton.classList.add('loading');
        }
        if (recommendationsAside) {
            recommendationsAside.classList.add('is-loading');
        }
    }

    // Hide loading state
    function hideLoading() {
        if (refreshButton) {
            refreshButton.style.pointerEvents = 'auto';
            refreshButton.classList.remove('loading');
        }
        if (recommendationsAside) {
            recommendationsAside.classList.remove('is-loading');
        }
    }

    async function fetchAIRecommendations() {
        showLoading();

        try {
            // patientDataForAI is passed from PHP in dashboard.php
            const response = await fetch('generate_recommendations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(patientDataForAI)
            });

            if (!response.ok) {
                if (response.status === 429) {
                    if (overviewText) overviewText.textContent = 'Rate limit exceeded. Please wait and try again.';
                    if (riskList) riskList.innerHTML = '<li>Rate limit exceeded. Please wait and try again.</li>';
                    if (precautionList) precautionList.innerHTML = '<li>Rate limit exceeded. Please wait and try again.</li>';
                    hideLoading();
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                console.error("AI Recommendation Error:", data.error, data.details || '');
                if (overviewText) overviewText.textContent = `Error generating summary: ${data.error}`;
                if (riskList) riskList.innerHTML = `<li>Error generating risks: ${data.error}</li>`;
                if (precautionList) precautionList.innerHTML = `<li>Error generating precautions: ${data.error}</li>`; // Display error for precautions
                return;
            }

            if (overviewText) {
                overviewText.textContent = data.overview;
            }

            if (riskList) {
                riskList.innerHTML = '';
                if (data.health_risks && data.health_risks.length > 0) {
                    data.health_risks.forEach(risk => {
                        const listItem = document.createElement('li');
                        listItem.innerHTML = `
                            <i class="material-symbols-outlined error">error</i>
                            <div>
                                <h4>${risk.title}</h4>
                                <p>${risk.description}</p>
                            </div>
                        `;
                        riskList.appendChild(listItem);
                    });
                } else {
                    riskList.innerHTML = '<li>No specific health risks identified based on current data.</li>';
                }
            }

            if (precautionList) {
                precautionList.innerHTML = '';
                if (data.precautionary_measures && data.precautionary_measures.length > 0) {
                    data.precautionary_measures.forEach(precaution => {
                        const listItem = document.createElement('li');
                        listItem.innerHTML = `
                            <i class="material-symbols-outlined">spa</i> <!-- Or another appropriate icon -->
                            <div>
                                <h4>${precaution.title}</h4>
                                <p>${precaution.description}</p>
                            </div>
                        `;
                        precautionList.appendChild(listItem);
                    });
                } else {
                    precautionList.innerHTML = '<li>No specific precautionary measures suggested based on current data.</li>';
                }
            }

        } catch (error) {
            console.error("Failed to fetch AI recommendations:", error);
            if (overviewText) overviewText.textContent = 'Failed to load recommendations. Please try again.';
            if (riskList) riskList.innerHTML = '<li>Failed to load risks.</li>';
            if (precautionList) precautionList.innerHTML = '<li>Failed to load precautions.</li>';
        } finally {
            hideLoading();
        }
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', fetchAIRecommendations);
    }

    fetchAIRecommendations();

    const style = document.createElement('style');
    style.innerHTML = `
        .loading-spinner {
            display: inline-block;
            font-style: italic;
            color: var(--text-secondary);
            font-size: 0.95rem;
            animation: pulse-opacity 1.5s infinite ease-in-out;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 20; /* Ensure spinner is above the overlay */
            background: none;
            padding: 0.5em 1em;
            border-radius: 8px;
            box-shadow: none;
        }
        .loading-spinner::after {
            content: ''; /* No dots, replaced by pulse animation */
        }

        /* Pulse opacity animation */
        @keyframes pulse-opacity {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        /* Spinning refresh icon */
        .refresh-ai {
            cursor: pointer;
            transition: transform 0.5s ease-in-out;
        }
        .refresh-ai.loading {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loading overlay for the whole recommendations section */
        .recommendations.is-loading {
            position: relative;
            pointer-events: none; /* Disable interaction */
        }

        .recommendations.is-loading::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(39, 35, 58, 0.7);
            backdrop-filter: blur(3px);
            z-index: 10; /* Overlay sits below spinner */
            border-radius: 15px;
        }

        .recommendations.is-loading .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 11;
            font-size: 1.2rem; /* Larger for central spinner */
            font-weight: 600;
            color: var(--accent-pink); /* Use accent color for main spinner */
            text-align: center;
            width: 100%; /* Ensure it spans the width for centering text */
            padding: 0 20px; /* Padding for text */
            box-sizing: border-box;
        }

        /* Hide content temporarily or dim it when loading */
        .recommendations.is-loading .recommendation-card h3,
        .recommendations.is-loading .recommendation-card p,
        .recommendations.is-loading .recommendation-card ul {
            /* This will dim the underlying text, keeping content visible but indicating loading */
            opacity: 0.3;
            transition: opacity 0.5s ease;
        }

        /* Don't hide the loading spinner itself */
        .recommendations.is-loading .loading-spinner {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
});
