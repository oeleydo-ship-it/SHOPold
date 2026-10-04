@props([
    'name' => 'content',
    'value' => '',
    'placeholder' => 'Start writing...',
    'required' => false,
    'label' => null,
    'height' => '400px'
])

<div x-data="richEditor('{{ $name }}', @js($value))" x-init="init()" class="rich-editor-wrapper">
    @if($label)
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    
    <!-- Editor Toolbar -->
    <div class="border border-b-0 rounded-t-lg bg-gray-50 p-2">
        <!-- Top Row: Mode Toggle & Actions -->
        <div class="flex items-center justify-between mb-2 pb-2 border-b">
            <div class="flex items-center gap-2">
                <button type="button" @click="toggleSource()" 
                        :class="sourceMode ? 'bg-gray-200' : 'bg-white'"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg border hover:bg-gray-100 transition">
                    <i class="fas fa-code mr-1"></i>
                    <span x-text="sourceMode ? 'Visual' : 'Source'"></span>
                </button>
                <button type="button" @click="$refs.mediaModal.showModal()" 
                        class="px-3 py-1.5 text-sm font-medium rounded-lg border bg-white hover:bg-gray-100 transition">
                    <i class="fas fa-image mr-1"></i>Add Media
                </button>
                <button type="button" @click="showBlocksPanel = !showBlocksPanel" 
                        :class="showBlocksPanel ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-white'"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg border hover:bg-gray-100 transition">
                    <i class="fas fa-th-large mr-1"></i>UI Blocks
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="fullscreen = !fullscreen" 
                        class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded">
                    <i :class="fullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>
            </div>
        </div>
        
        <!-- Formatting Toolbar -->
        <div x-show="!sourceMode" class="flex flex-wrap items-center gap-1">
            <!-- Text Format -->
            <select @change="execCommand('formatBlock', $event.target.value); $event.target.value = ''" 
                    class="px-2 py-1 text-sm border rounded bg-white">
                <option value="">Paragraph</option>
                <option value="p">Paragraph</option>
                <option value="h1">Heading 1</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
                <option value="h4">Heading 4</option>
                <option value="pre">Code Block</option>
                <option value="blockquote">Quote</option>
            </select>
            
            <!-- Font Size -->
            <select @change="execCommand('fontSize', $event.target.value); $event.target.value = ''" 
                    class="px-2 py-1 text-sm border rounded bg-white">
                <option value="">Size</option>
                <option value="1">Small</option>
                <option value="3">Normal</option>
                <option value="5">Large</option>
                <option value="7">Huge</option>
            </select>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Basic Formatting -->
            <button type="button" @click="execCommand('bold')" title="Bold" class="toolbar-btn">
                <i class="fas fa-bold"></i>
            </button>
            <button type="button" @click="execCommand('italic')" title="Italic" class="toolbar-btn">
                <i class="fas fa-italic"></i>
            </button>
            <button type="button" @click="execCommand('underline')" title="Underline" class="toolbar-btn">
                <i class="fas fa-underline"></i>
            </button>
            <button type="button" @click="execCommand('strikeThrough')" title="Strikethrough" class="toolbar-btn">
                <i class="fas fa-strikethrough"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Text Color -->
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" title="Text Color" class="toolbar-btn">
                    <i class="fas fa-font"></i>
                    <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4 h-1 bg-red-500 rounded-sm"></span>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute top-full left-0 mt-1 p-2 bg-white border rounded-lg shadow-lg z-50 grid grid-cols-6 gap-1">
                    <template x-for="color in ['#000000','#e53e3e','#dd6b20','#d69e2e','#38a169','#3182ce','#805ad5','#d53f8c','#718096','#ffffff']">
                        <button type="button" @click="execCommand('foreColor', color); open = false" 
                                :style="'background-color:' + color" 
                                class="w-6 h-6 rounded border border-gray-300"></button>
                    </template>
                </div>
            </div>
            
            <!-- Background Color -->
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" title="Background Color" class="toolbar-btn">
                    <i class="fas fa-highlighter"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute top-full left-0 mt-1 p-2 bg-white border rounded-lg shadow-lg z-50 grid grid-cols-6 gap-1">
                    <template x-for="color in ['transparent','#fef3c7','#fed7d7','#c6f6d5','#bee3f8','#e9d8fd','#fbb6ce','#e2e8f0','#fefcbf','#b2f5ea']">
                        <button type="button" @click="execCommand('hiliteColor', color); open = false" 
                                :style="'background-color:' + color" 
                                class="w-6 h-6 rounded border border-gray-300"></button>
                    </template>
                </div>
            </div>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Lists -->
            <button type="button" @click="execCommand('insertUnorderedList')" title="Bullet List" class="toolbar-btn">
                <i class="fas fa-list-ul"></i>
            </button>
            <button type="button" @click="execCommand('insertOrderedList')" title="Numbered List" class="toolbar-btn">
                <i class="fas fa-list-ol"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Alignment -->
            <button type="button" @click="execCommand('justifyLeft')" title="Align Left" class="toolbar-btn">
                <i class="fas fa-align-left"></i>
            </button>
            <button type="button" @click="execCommand('justifyCenter')" title="Align Center" class="toolbar-btn">
                <i class="fas fa-align-center"></i>
            </button>
            <button type="button" @click="execCommand('justifyRight')" title="Align Right" class="toolbar-btn">
                <i class="fas fa-align-right"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Links & Media -->
            <button type="button" @click="insertLink()" title="Insert Link" class="toolbar-btn">
                <i class="fas fa-link"></i>
            </button>
            <button type="button" @click="execCommand('unlink')" title="Remove Link" class="toolbar-btn">
                <i class="fas fa-unlink"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Table -->
            <button type="button" @click="insertTable()" title="Insert Table" class="toolbar-btn">
                <i class="fas fa-table"></i>
            </button>
            
            <!-- Horizontal Rule -->
            <button type="button" @click="execCommand('insertHorizontalRule')" title="Horizontal Line" class="toolbar-btn">
                <i class="fas fa-minus"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Indent -->
            <button type="button" @click="execCommand('indent')" title="Indent" class="toolbar-btn">
                <i class="fas fa-indent"></i>
            </button>
            <button type="button" @click="execCommand('outdent')" title="Outdent" class="toolbar-btn">
                <i class="fas fa-outdent"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Undo/Redo -->
            <button type="button" @click="execCommand('undo')" title="Undo" class="toolbar-btn">
                <i class="fas fa-undo"></i>
            </button>
            <button type="button" @click="execCommand('redo')" title="Redo" class="toolbar-btn">
                <i class="fas fa-redo"></i>
            </button>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            
            <!-- Clear Formatting -->
            <button type="button" @click="execCommand('removeFormat')" title="Clear Formatting" class="toolbar-btn">
                <i class="fas fa-eraser"></i>
            </button>
        </div>
    </div>
    
    <!-- UI Blocks Panel -->
    <div x-show="showBlocksPanel" x-collapse class="border border-t-0 bg-blue-50 p-4">
        <h4 class="font-semibold text-blue-900 mb-3"><i class="fas fa-th-large mr-2"></i>Insert UI Block</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Feature Box -->
            <button type="button" @click="insertBlock('feature')" class="ui-block-btn">
                <i class="fas fa-star text-yellow-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Feature Box</span>
            </button>
            
            <!-- Call to Action -->
            <button type="button" @click="insertBlock('cta')" class="ui-block-btn">
                <i class="fas fa-bullhorn text-blue-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Call to Action</span>
            </button>
            
            <!-- Info Box -->
            <button type="button" @click="insertBlock('info')" class="ui-block-btn">
                <i class="fas fa-info-circle text-cyan-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Info Box</span>
            </button>
            
            <!-- Warning Box -->
            <button type="button" @click="insertBlock('warning')" class="ui-block-btn">
                <i class="fas fa-exclamation-triangle text-orange-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Warning Box</span>
            </button>
            
            <!-- Success Box -->
            <button type="button" @click="insertBlock('success')" class="ui-block-btn">
                <i class="fas fa-check-circle text-green-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Success Box</span>
            </button>
            
            <!-- Two Columns -->
            <button type="button" @click="insertBlock('columns2')" class="ui-block-btn">
                <i class="fas fa-columns text-purple-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">2 Columns</span>
            </button>
            
            <!-- Three Columns -->
            <button type="button" @click="insertBlock('columns3')" class="ui-block-btn">
                <i class="fas fa-th text-indigo-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">3 Columns</span>
            </button>
            
            <!-- Image with Text -->
            <button type="button" @click="insertBlock('imageText')" class="ui-block-btn">
                <i class="fas fa-image text-pink-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Image + Text</span>
            </button>
            
            <!-- Testimonial -->
            <button type="button" @click="insertBlock('testimonial')" class="ui-block-btn">
                <i class="fas fa-quote-left text-gray-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Testimonial</span>
            </button>
            
            <!-- Pricing Card -->
            <button type="button" @click="insertBlock('pricing')" class="ui-block-btn">
                <i class="fas fa-tag text-green-600 text-xl mb-2"></i>
                <span class="text-xs font-medium">Pricing Card</span>
            </button>
            
            <!-- FAQ Item -->
            <button type="button" @click="insertBlock('faq')" class="ui-block-btn">
                <i class="fas fa-question-circle text-blue-600 text-xl mb-2"></i>
                <span class="text-xs font-medium">FAQ Item</span>
            </button>
            
            <!-- Video Embed -->
            <button type="button" @click="insertBlock('video')" class="ui-block-btn">
                <i class="fas fa-video text-red-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Video Embed</span>
            </button>
            
            <!-- Button -->
            <button type="button" @click="insertBlock('button')" class="ui-block-btn">
                <i class="fas fa-hand-pointer text-blue-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Button</span>
            </button>
            
            <!-- Divider -->
            <button type="button" @click="insertBlock('divider')" class="ui-block-btn">
                <i class="fas fa-grip-lines text-gray-400 text-xl mb-2"></i>
                <span class="text-xs font-medium">Divider</span>
            </button>
            
            <!-- Specs Table -->
            <button type="button" @click="insertBlock('specs')" class="ui-block-btn">
                <i class="fas fa-list-alt text-teal-500 text-xl mb-2"></i>
                <span class="text-xs font-medium">Specs Table</span>
            </button>
            
            <!-- Gallery Grid -->
            <button type="button" @click="insertBlock('gallery')" class="ui-block-btn">
                <i class="fas fa-images text-purple-600 text-xl mb-2"></i>
                <span class="text-xs font-medium">Gallery Grid</span>
            </button>
        </div>
    </div>
    
    <!-- Editor Content Area -->
    <div :class="fullscreen ? 'fixed inset-0 z-50 bg-white' : ''">
        <!-- Visual Editor -->
        <div x-ref="editor"
             x-bind:style="sourceMode ? 'display: none' : ''"
             contenteditable="true"
             @input="updateContent()"
             @paste="handlePaste($event)"
             class="border rounded-b-lg p-4 bg-white prose max-w-none focus:outline-none focus:ring-2 focus:ring-blue-500 overflow-auto"
             style="min-height: {{ $height }};"
             data-placeholder="{{ $placeholder }}"></div>
        
        <!-- Source Editor -->
        <textarea x-ref="sourceEditor"
                  x-bind:style="!sourceMode ? 'display: none' : ''"
                  @input="updateFromSource()"
                  class="w-full border rounded-b-lg p-4 font-mono text-sm bg-gray-900 text-green-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  style="min-height: {{ $height }};"></textarea>
    </div>
    
    <!-- Hidden Input for Form Submission -->
    <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" :value="content" {{ $required ? 'required' : '' }}>
    
    <!-- Media Modal -->
    <dialog x-ref="mediaModal" class="rounded-xl shadow-2xl p-0 w-full max-w-4xl backdrop:bg-black/50">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Insert Media</h3>
                <button type="button" @click="$refs.mediaModal.close()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div x-data="{ mediaUrl: '', mediaAlt: '' }">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                        <input type="text" x-model="mediaUrl" placeholder="https://example.com/image.jpg" 
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alt Text</label>
                        <input type="text" x-model="mediaAlt" placeholder="Image description" 
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="$refs.mediaModal.close()" 
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="insertImage(mediaUrl, mediaAlt); $refs.mediaModal.close()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Insert</button>
                    </div>
                </div>
            </div>
        </div>
    </dialog>
</div>

<style>
.rich-editor-wrapper .toolbar-btn {
    padding: 0.5rem;
    color: #4b5563;
    border-radius: 0.25rem;
    transition: all 0.15s;
}
.rich-editor-wrapper .toolbar-btn:hover {
    color: #111827;
    background-color: #f3f4f6;
}
.rich-editor-wrapper .ui-block-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
}
.rich-editor-wrapper .ui-block-btn:hover {
    background-color: #dbeafe;
    border-color: #93c5fd;
}
.rich-editor-wrapper [contenteditable]:empty:before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
}
.rich-editor-wrapper [contenteditable] {
    outline: none;
}
.rich-editor-wrapper .prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
.rich-editor-wrapper .prose table {
    width: 100%;
    border-collapse: collapse;
}
.rich-editor-wrapper .prose table td,
.rich-editor-wrapper .prose table th {
    border: 1px solid #e5e7eb;
    padding: 0.5rem;
}
/* UI Block Styles */
.rich-editor-wrapper .ui-block {
    margin: 1rem 0;
    padding: 1rem;
    border-radius: 0.5rem;
}
.rich-editor-wrapper .ui-block-feature {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
}
.rich-editor-wrapper .ui-block-cta {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 1px solid #3b82f6;
    text-align: center;
}
.rich-editor-wrapper .ui-block-info {
    background: #ecfeff;
    border-left: 4px solid #06b6d4;
}
.rich-editor-wrapper .ui-block-warning {
    background: #fff7ed;
    border-left: 4px solid #f97316;
}
.rich-editor-wrapper .ui-block-success {
    background: #f0fdf4;
    border-left: 4px solid #22c55e;
}
</style>
