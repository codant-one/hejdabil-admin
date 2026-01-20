"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * This is an advanced example for creating icon bundles for Iconify SVG Framework.
 *
 * It creates a bundle from:
 * - All SVG files in a directory.
 * - Custom JSON files.
 * - Iconify icon sets.
 * - SVG framework.
 *
 * This example uses Iconify Tools to import and clean up icons.
 * For Iconify Tools documentation visit https://docs.iconify.design/tools/tools2/
 */
const fs_1 = require("fs");
const path_1 = require("path");
// Installation: npm install --save-dev @iconify/tools @iconify/utils @iconify/json @iconify/iconify
const tools_1 = require("@iconify/tools");
const utils_1 = require("@iconify/utils");

// Allow SVG <image> usage in custom icons.
// Iconify Tools marks <image>/<feImage> as "bad tags" by default because they can reference
// external resources. This project uses a few custom SVGs that rely on <image>, so we opt-in.
(function allowImageTagsInIconifyTools() {
    try {
        // Internal API, but stable enough for a build script.
        // eslint-disable-next-line @typescript-eslint/no-var-requires
        const tags = require('@iconify/tools/lib/svg/data/tags.cjs');
        if (tags?.badTags?.delete) {
            tags.badTags.delete('image');
            tags.badTags.delete('feImage');
        }
    }
    catch (err) {
        // Ignore if paths change in future versions.
    }
})();
const sources = {
    svg: [
        {
            dir: 'src/assets/images/iconify-svg',
            monotone: false,
            prefix: 'custom',
        },
        // {
        //   dir: 'emojis',
        //   monotone: false,
        //   prefix: 'emoji',
        // },
    ],
    icons: [
    // 'mdi:home',
    // 'mdi:account',
    // 'mdi:login',
    // 'mdi:logout',
    // 'octicon:book-24',
    // 'octicon:code-square-24',
    ],
    json: [
        // Custom JSON file
        // 'json/gg.json',
        // Iconify JSON file (@iconify/json is a package name, /json/ is directory where files are, then filename)
        require.resolve('@iconify-json/tabler/icons.json'),
        {
            filename: require.resolve('@iconify-json/fa/icons.json'),
            icons: [
                'facebook',
                'google',
                'twitter',
                'circle',
            ],
        },
        // Custom file with only few icons
        // {
        //   filename: require.resolve('@iconify-json/line-md/icons.json'),
        //   icons: [
        //     'home-twotone-alt',
        //     'github',
        //     'document-list',
        //     'document-code',
        //     'image-twotone',
        //   ],
        // },
    ],
};
// Iconify component (this changes import statement in generated file)
// Available options: '@iconify/react' for React, '@iconify/vue' for Vue 3, '@iconify/vue2' for Vue 2, '@iconify/svelte' for Svelte
const component = '@iconify/vue';
// Set to true to use require() instead of import
const commonJS = false;
// File to save bundle to
const target = (0, path_1.join)(__dirname, 'icons-bundle.js');
/**
 * Do stuff!
 */
// eslint-disable-next-line sonarjs/cognitive-complexity
(async function () {
    let bundle = commonJS
        ? `const { addCollection } = require('${component}');\n\n`
        : `import { addCollection } from '${component}';\n\n`;
    // Create directory for output if missing
    const dir = (0, path_1.dirname)(target);
    try {
        await fs_1.promises.mkdir(dir, {
            recursive: true,
        });
    }
    catch (err) {
        //
    }
    /**
     * Convert sources.icons to sources.json
     */
    if (sources.icons) {
        const sourcesJSON = sources.json ? sources.json : (sources.json = []);
        // Sort icons by prefix
        const organizedList = organizeIconsList(sources.icons);
        for (const prefix in organizedList) {
            const filename = require.resolve(`@iconify/json/json/${prefix}.json`);
            sourcesJSON.push({
                filename,
                icons: organizedList[prefix],
            });
        }
    }
    /**
     * Bundle JSON files
     */
    if (sources.json) {
        for (let i = 0; i < sources.json.length; i++) {
            const item = sources.json[i];
            // Load icon set
            const filename = typeof item === 'string' ? item : item.filename;
            let content = JSON.parse(await fs_1.promises.readFile(filename, 'utf8'));
            // Filter icons
            if (typeof item !== 'string' && item.icons?.length) {
                const filteredContent = (0, utils_1.getIcons)(content, item.icons);
                if (!filteredContent)
                    throw new Error(`Cannot find required icons in ${filename}`);
                content = filteredContent;
            }
            // Remove metadata and add to bundle
            removeMetaData(content);
            (0, utils_1.minifyIconSet)(content);
            bundle += `addCollection(${JSON.stringify(content)});\n`;
            console.log(`Bundled icons from ${filename}`);
        }
    }
    /**
     * Custom SVG
     */
    if (sources.svg) {
        for (let i = 0; i < sources.svg.length; i++) {
            const source = sources.svg[i];
            // Import icons with error handling per file
            const iconSet = (0, tools_1.blankIconSet)(source.prefix);
            
            // Manually import SVG files from directory
            try {
                const files = await fs_1.promises.readdir(source.dir);
                const svgFiles = files.filter(file => file.endsWith('.svg'));
                
                for (const file of svgFiles) {
                    const filePath = (0, path_1.join)(source.dir, file);
                    const name = file.replace('.svg', '');

                    let svg;
                    try {
                        const content = await fs_1.promises.readFile(filePath, 'utf8');
                        svg = new tools_1.SVG(content);
                    }
                    catch (err) {
                        // If file cannot be read/parsed, there's nothing to bundle.
                        continue;
                    }

                    // Clean up and optimise icons. If any step fails, keep going and attempt to bundle
                    // the raw SVG anyway (goal: always bundle, never fail build for a single icon).
                    try {
                        await (0, tools_1.cleanupSVG)(svg);
                    }
                    catch (err) {
                        // ignore
                    }

                    if (source.monotone) {
                        try {
                            await (0, tools_1.parseColors)(svg, {
                                defaultColor: 'currentColor',
                                callback: (attr, colorStr, color) => {
                                    return !color || (0, tools_1.isEmptyColor)(color)
                                        ? colorStr
                                        : 'currentColor';
                                },
                            });
                        }
                        catch (err) {
                            // ignore
                        }
                    }

                    try {
                        await (0, tools_1.runSVGO)(svg);
                    }
                    catch (err) {
                        // ignore
                    }

                    try {
                        iconSet.fromSVG(name, svg);
                    }
                    catch (err) {
                        // ignore
                    }
                }
                
                console.log(`Bundled ${iconSet.count()} icons from ${source.dir}`);
                
                // Export to JSON only if we have icons
                if (iconSet.count() > 0) {
                    const content = iconSet.export();
                    bundle += `addCollection(${JSON.stringify(content)});\n`;
                }
            }
            catch (err) {
                // ignore
            }
        }
    }
    // Save to file
    await fs_1.promises.writeFile(target, bundle, 'utf8');
    console.log(`Saved ${target} (${bundle.length} bytes)`);
})().catch(err => {
    console.error(err);
});
/**
 * Remove metadata from icon set
 */
function removeMetaData(iconSet) {
    const props = [
        'info',
        'chars',
        'categories',
        'themes',
        'prefixes',
        'suffixes',
    ];
    props.forEach(prop => {
        delete iconSet[prop];
    });
}
/**
 * Sort icon names by prefix
 */
function organizeIconsList(icons) {
    const sorted = Object.create(null);
    icons.forEach(icon => {
        const item = (0, utils_1.stringToIcon)(icon);
        if (!item)
            return;
        const prefix = item.prefix;
        const prefixList = sorted[prefix]
            ? sorted[prefix]
            : (sorted[prefix] = []);
        const name = item.name;
        if (!prefixList.includes(name))
            prefixList.push(name);
    });
    return sorted;
}
