<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Progressive JSON Stream Viewer - Vue</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0d1117;
            color: #58a6ff;
            padding: 1em;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #f0883e;
            border-bottom: 2px solid #30363d;
            padding-bottom: 0.5em;
        }
        button {
            background: #238636;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 1em;
        }
        button:hover {
            background: #2ea043;
        }
        button:disabled {
            background: #484f58;
            cursor: not-allowed;
        }
        .status {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 1em;
            font-weight: bold;
        }
        .status.streaming { background: #1f6feb; color: white; }
        .status.complete { background: #238636; color: white; }
        .status.error { background: #da3633; color: white; }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            border: 1px solid #30363d;
            padding: 1em;
            margin-bottom: 2em;
            background: #161b22;
            border-radius: 6px;
            font-size: 12px;
            /*max-height: 400px;*/
            overflow-y: auto;
        }
        .object-view {
            background: #161b22;
            padding: 1em;
            border-radius: 6px;
            border: 1px solid #30363d;
        }
        .object-view ul {
            list-style-type: none;
            padding-left: 1.5em;
            margin: 0;
        }
        .object-view > ul {
            padding-left: 0;
        }
        .object-view li {
            margin-bottom: 0.5em;
            line-height: 1.4;
        }
        .key {
            color: #79c0ff;
            font-weight: bold;
        }
        .string-value {
            color: #a5d6ff;
        }
        .number-value {
            color: #79c0ff;
        }
        .placeholder {
            color: #f85149;
            font-style: italic;
        }
        .resolved {
            color: #56d364;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2em;
            margin-top: 1em;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div id="app" class="container">
    <h1>Progressive JSON Stream Viewer</h1>

    <button @click="startStreaming" :disabled="isStreaming">
        {{ isStreaming ? 'Streaming...' : 'Start Streaming' }}
    </button>

    <div v-if="status" :class="['status', statusClass]">
        {{ status }}
    </div>

    <div class="grid">
        <div>
            <h2>Raw Stream Data</h2>
            <pre>{{ rawStream || 'No data received yet...' }}</pre>
        </div>

        <div>
            <h2>Reconstructed Object</h2>
            <div class="object-view">
                <template v-if="Object.keys(finalObject).length">
                    <object-renderer :obj="finalObject" />
                </template>
                <p v-else style="color: #8b949e; font-style: italic;">
                    Waiting for stream data...
                </p>
            </div>
        </div>
    </div>

    <div style="margin-top: 2em;">
        <h2>Debug Info</h2>
        <div class="object-view">
            <p><strong>Chunks Received:</strong> {{ chunksReceived }}</p>
            <p><strong>Placeholders Resolved:</strong> {{ placeholdersResolved }}</p>
            <p><strong>Base Object Parsed:</strong> {{ baseObjectParsed ? 'Yes' : 'No' }}</p>
        </div>
    </div>
</div>

<script>
    const { createApp } = Vue;

    // Recursive component for rendering nested objects
    const ObjectRenderer = {
        name: 'ObjectRenderer',
        props: ['obj'],
        template: `
            <ul>
                <li v-for="(value, key) in obj" :key="key">
                    <span class="key">{{ key }}:</span>
                    <template v-if="isPlaceholder(value)">
                        <span class="placeholder"> {{ value }} (pending...)</span>
                    </template>
                    <template v-else-if="typeof value === 'object' && value !== null && !Array.isArray(value)">
                        <object-renderer :obj="value" />
                    </template>
                    <template v-else-if="Array.isArray(value)">
                        <span class="resolved">[Array with {{ value.length }} items]</span>
                        <ul style="margin-top: 0.5em;">
                            <li v-for="(item, index) in value" :key="index">
                                <span class="key">[{{ index }}]:</span>
                                <template v-if="typeof item === 'object' && item !== null">
                                    <object-renderer :obj="item" />
                                </template>
                                <template v-else>
                                    <span :class="getValueClass(item)"> {{ formatValue(item) }}</span>
                                </template>
                            </li>
                        </ul>
                    </template>
                    <template v-else>
                        <span :class="getValueClass(value)"> {{ formatValue(value) }}</span>
                    </template>
                </li>
            </ul>
        `,
        methods: {
            isPlaceholder(value) {
                return typeof value === 'string' && value.startsWith('$');
            },
            getValueClass(value) {
                if (typeof value === 'string') return 'string-value';
                if (typeof value === 'number') return 'number-value';
                return 'resolved';
            },
            formatValue(value) {
                if (typeof value === 'string') return `"${value}"`;
                return String(value);
            }
        }
    };

    createApp({
        components: {
            ObjectRenderer
        },
        data() {
            return {
                rawStream: '',
                finalObject: {},
                isStreaming: false,
                status: '',
                statusClass: '',
                chunksReceived: 0,
                placeholdersResolved: 0,
                baseObjectParsed: false
            };
        },
        methods: {
            async startStreaming() {
                this.resetState();
                this.isStreaming = true;
                this.setStatus('Starting stream...', 'streaming');

                try {
                    // Use a test endpoint or replace with your actual endpoint
                    const response = await fetch('/test', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/x-json-stream',
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    this.setStatus('Stream connected, receiving data...', 'streaming');
                    await this.processStream(response);

                } catch (error) {
                    console.error('Streaming error:', error);
                    this.setStatus(`Error: ${error.message}`, 'error');
                } finally {
                    this.isStreaming = false;
                }
            },

            async processStream(response) {
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';

                try {
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        const chunk = decoder.decode(value, { stream: true });
                        this.rawStream += chunk;
                        buffer += chunk;
                        this.chunksReceived++;

                        // Parse base object once (look for first complete JSON object)
                        if (!this.baseObjectParsed) {
                            const jsonMatch = buffer.match(/^({[\s\S]*?})\s*(?=\/\*|$)/);
                            if (jsonMatch) {
                                try {
                                    this.finalObject = JSON.parse(jsonMatch[1]);
                                    this.baseObjectParsed = true;
                                    this.setStatus('Base structure received, waiting for data...', 'streaming');
                                } catch (e) {
                                    console.error('Failed to parse base object:', e);
                                }
                            }
                        }

                        // Process placeholder data with improved regex for dot notation
                        // Updated regex to handle dot notation keys
                        const placeholderRegex = /\/\*\s*\s*\$([a-zA-Z0-9_.]+)\s*\*\/\s*\n([\s\S]*?)(?=\n\/\*|$)/g;
                        let match;
                        while ((match = placeholderRegex.exec(buffer)) !== null) {
                            const placeholderKey = match[1]; // e.g., "products.desk.price"
                            const placeholderData = match[2].trim();

                            try {
                                const parsedValue = JSON.parse(placeholderData);
                                this.resolvePlaceholder(placeholderKey, parsedValue);
                                this.placeholdersResolved++;
                            } catch (e) {
                                console.error(`Failed to parse placeholder $${placeholderKey}:`, e);
                            }
                        }

                        // Handle errors in stream
                        const errorRegex = /\/\*\s*\s*\$([a-zA-Z0-9_.]+)\s*\*\/\s*\n([\s\S]*?)(?=\n\/\*|$)/g;
                        let errorMatch;
                        while ((errorMatch = errorRegex.exec(buffer)) !== null) {
                            const errorKey = errorMatch[1];
                            try {
                                const errorData = JSON.parse(errorMatch[2].trim());
                                console.error(`Placeholder error for ${errorKey}:`, errorData);
                                this.resolvePlaceholder(errorKey, `[ERROR: ${errorData.message}]`);
                            } catch (e) {
                                console.error('Failed to parse error data:', e);
                            }
                        }
                    }

                    this.setStatus('Stream completed successfully!', 'complete');

                } catch (error) {
                    throw error;
                } finally {
                    reader.releaseLock();
                }
            },

            resolvePlaceholder(dotNotationKey, value) {
                // Handle Laravel dot notation (e.g., "products.desk.price")
                const keys = dotNotationKey.split('.');
                const placeholderReference = '$' + dotNotationKey;

                // Recursively find and replace the placeholder
                const findAndReplace = (obj, keyPath = []) => {
                    if (typeof obj !== 'object' || obj === null) return;

                    for (const key in obj) {
                        const currentPath = [...keyPath, key];
                        const currentDotPath = currentPath.join('.');

                        if (obj[key] === placeholderReference) {
                            // Found the placeholder, replace it
                            obj[key] = value;
                            console.log(`Resolved placeholder: ${placeholderReference} -> `, value);
                        } else if (typeof obj[key] === 'object' && obj[key] !== null) {
                            // Recursively search nested objects
                            findAndReplace(obj[key], currentPath);
                        }
                    }
                };

                findAndReplace(this.finalObject);

                // Force reactivity update
                this.finalObject = { ...this.finalObject };
            },

            resetState() {
                this.rawStream = '';
                this.finalObject = {};
                this.chunksReceived = 0;
                this.placeholdersResolved = 0;
                this.baseObjectParsed = false;
                this.status = '';
                this.statusClass = '';
            },

            setStatus(message, type) {
                this.status = message;
                this.statusClass = type;
            }
        }
    }).mount('#app');
</script>
</body>
</html>
