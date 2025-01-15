const resolveRelativeStatePath = function (containerPath, path, isAbsolute) {
    let containerPathCopy = containerPath

    if (path.startsWith('/')) {
        isAbsolute = true
        path = path.slice(1)
    }

    if (isAbsolute) {
        return path
    }

    while (path.startsWith('../')) {
        containerPathCopy = containerPathCopy.includes('.')
            ? containerPathCopy.slice(0, containerPathCopy.lastIndexOf('.'))
            : null

        path = path.slice(3)
    }

    if (['', null, undefined].includes(containerPathCopy)) {
        return path
    }

    return `${containerPathCopy}.${path}`
}

const makeGetUtility = function (containerPath) {
    return (path, isAbsolute) => {
        return this.$wire.$get(
            resolveRelativeStatePath(containerPath, path, isAbsolute),
        )
    }
}

const makeSetUtility = function (containerPath, isComponentLive) {
    return (path, state, isAbsolute, isLive = null) => {
        isLive ??= isComponentLive

        return this.$wire.$set(
            resolveRelativeStatePath(containerPath, path, isAbsolute),
            state,
            isLive,
        )
    }
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('filamentSchema', ({ livewireId }) => ({
        handleFormValidationError: function (event) {
            if (event.detail.livewireId !== livewireId) {
                return
            }

            this.$nextTick(() => {
                let error = this.$el.querySelector('[data-validation-error]')

                if (!error) {
                    return
                }

                let elementToExpand = error

                while (elementToExpand) {
                    elementToExpand.dispatchEvent(new CustomEvent('expand'))

                    elementToExpand = elementToExpand.parentNode
                }

                setTimeout(
                    () =>
                        error.closest('[data-field-wrapper]').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                            inline: 'start',
                        }),
                    200,
                )
            })
        },
    }))

    window.Alpine.data(
        'filamentSchemaComponent',
        ({ path, containerPath, isLive }) => ({
            $statePath: path,
            get $get() {
                return makeGetUtility(containerPath)
            },
            get $set() {
                return makeSetUtility(containerPath, isLive)
            },
            get $state() {
                return this.$wire.$get(path)
            },
        }),
    )
})
