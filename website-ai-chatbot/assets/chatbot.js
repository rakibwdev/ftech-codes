document.addEventListener(
    'DOMContentLoaded',
    function () {

        const openButton =
            document.getElementById(
                'wac-open'
            );

        const closeButton =
            document.getElementById(
                'wac-close'
            );

        const chatWindow =
            document.getElementById(
                'wac-window'
            );

        const form =
            document.getElementById(
                'wac-form'
            );

        const input =
            document.getElementById(
                'wac-input'
            );

        const messages =
            document.getElementById(
                'wac-messages'
            );


        /*
         * Open chatbot
         */
        openButton.addEventListener(
            'click',
            function () {

                chatWindow.classList.add(
                    'active'
                );

                input.focus();

            }
        );


        /*
         * Close chatbot
         */
        closeButton.addEventListener(
            'click',
            function () {

                chatWindow.classList.remove(
                    'active'
                );

            }
        );


        /*
         * Add message
         */
        function addMessage(
            message,
            type
        ) {

            const div =
                document.createElement(
                    'div'
                );


            div.className =
                'wac-message ' +
                type;


            div.textContent =
                message;


            messages.appendChild(
                div
            );


            messages.scrollTop =
                messages.scrollHeight;

        }


        /*
         * Submit question
         */
        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();


                const question =
                    input.value.trim();


                if (!question) {
                    return;
                }


                /*
                 * Show user message
                 */
                addMessage(
                    question,
                    'user'
                );


                input.value = '';

                input.disabled = true;


                /*
                 * Loading
                 */
                addMessage(
                    'Thinking...',
                    'bot loading'
                );


                try {

                    const response =
                        await fetch(
                            WAC.apiUrl,
                            {

                                method: 'POST',

                                headers: {
                                    'Content-Type':
                                        'application/json'
                                },

                                body:
                                    JSON.stringify(
                                        {
                                            message:
                                                question
                                        }
                                    )

                            }
                        );


                    const data =
                        await response.json();


                    /*
                     * Remove loading
                     */
                    const loading =
                        document.querySelector(
                            '.wac-message.loading'
                        );


                    if (loading) {
                        loading.remove();
                    }


                    if (
                        !response.ok
                    ) {

                        addMessage(
                            data.message ||
                            'Something went wrong.',
                            'bot'
                        );

                        return;
                    }


                    addMessage(
                        data.answer,
                        'bot'
                    );


                } catch (error) {

                    addMessage(
                        'Could not connect to the AI service.',
                        'bot'
                    );

                }


                input.disabled = false;

                input.focus();

            }
        );

    }
);