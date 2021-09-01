var registerBlockType = wp.blocks.registerBlockType;

registerBlockType('matomo/matomo-opt-out', {
    apiVersion: 2,
    title: 'Matomo opt out',
    icon: 'universal-access-alt',
    category: 'widgets',
    edit() {
        return (
            <div>Hello World (from the editor).</div>
        );
    },
    save() {

        return (
            <div>
                Hello World (from the frontend).
            </div>
        );
    },
});