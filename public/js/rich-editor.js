/**
 * Rich Text Editor with UI Blocks
 * Similar to CKEditor with toolbar, media insertion, and UI blocks
 */
function richEditor(name, initialValue) {
    return {
        content: initialValue || '',
        sourceMode: false,
        showBlocksPanel: false,
        fullscreen: false,
        
        init() {
            // Initialize editor content
            const self = this;
            this.$nextTick(() => {
                if (self.$refs.editor) {
                    self.$refs.editor.innerHTML = self.content;
                }
                if (self.$refs.sourceEditor) {
                    self.$refs.sourceEditor.value = self.content;
                }
                if (self.$refs.hiddenInput) {
                    self.$refs.hiddenInput.value = self.content;
                }
            });
        },
        
        execCommand(command, value = null) {
            document.execCommand(command, false, value);
            this.$refs.editor.focus();
            this.updateContent();
        },
        
        updateContent() {
            this.content = this.$refs.editor.innerHTML;
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = this.content;
            }
        },
        
        updateFromSource() {
            this.content = this.$refs.sourceEditor.value;
            if (this.$refs.editor) {
                this.$refs.editor.innerHTML = this.content;
            }
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = this.content;
            }
        },
        
        toggleSource() {
            if (this.sourceMode) {
                // Switching from source to visual
                this.$refs.editor.innerHTML = this.$refs.sourceEditor.value;
                this.content = this.$refs.sourceEditor.value;
            } else {
                // Switching from visual to source
                this.$refs.sourceEditor.value = this.$refs.editor.innerHTML;
            }
            this.sourceMode = !this.sourceMode;
        },
        
        handlePaste(event) {
            event.preventDefault();
            const text = event.clipboardData.getData('text/html') || event.clipboardData.getData('text/plain');
            document.execCommand('insertHTML', false, text);
            this.updateContent();
        },
        
        insertLink() {
            const url = prompt('Enter URL:', 'https://');
            if (url) {
                this.execCommand('createLink', url);
            }
        },
        
        insertImage(url, alt) {
            if (url) {
                const img = `<img src="${url}" alt="${alt || ''}" class="max-w-full h-auto rounded-lg my-2">`;
                this.execCommand('insertHTML', img);
            }
        },
        
        insertTable() {
            const rows = prompt('Number of rows:', '3');
            const cols = prompt('Number of columns:', '3');
            if (rows && cols) {
                let table = '<table class="w-full border-collapse my-4"><tbody>';
                for (let i = 0; i < parseInt(rows); i++) {
                    table += '<tr>';
                    for (let j = 0; j < parseInt(cols); j++) {
                        if (i === 0) {
                            table += '<th class="border border-gray-300 p-2 bg-gray-100">Header</th>';
                        } else {
                            table += '<td class="border border-gray-300 p-2">Cell</td>';
                        }
                    }
                    table += '</tr>';
                }
                table += '</tbody></table>';
                this.execCommand('insertHTML', table);
            }
        },
        
        insertBlock(type) {
            let html = '';
            
            switch(type) {
                case 'feature':
                    html = `
                        <div class="ui-block ui-block-feature" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                <div style="font-size: 2rem;">⭐</div>
                                <div>
                                    <h4 style="font-weight: bold; margin-bottom: 0.5rem; color: #92400e;">Feature Title</h4>
                                    <p style="color: #78350f;">Describe your amazing feature here. Highlight what makes this special.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'cta':
                    html = `
                        <div class="ui-block ui-block-cta" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 1px solid #3b82f6; padding: 2rem; border-radius: 0.5rem; margin: 1rem 0; text-align: center;">
                            <h3 style="font-size: 1.5rem; font-weight: bold; color: #1e40af; margin-bottom: 0.5rem;">Call to Action Title</h3>
                            <p style="color: #1e3a8a; margin-bottom: 1rem;">Compelling message that encourages users to take action.</p>
                            <a href="#" style="display: inline-block; background: #2563eb; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Get Started</a>
                        </div>
                    `;
                    break;
                    
                case 'info':
                    html = `
                        <div class="ui-block ui-block-info" style="background: #ecfeff; border-left: 4px solid #06b6d4; padding: 1rem; border-radius: 0 0.5rem 0.5rem 0; margin: 1rem 0;">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <span style="color: #0891b2; font-size: 1.25rem;">ℹ️</span>
                                <div>
                                    <h4 style="font-weight: 600; color: #155e75; margin-bottom: 0.25rem;">Information</h4>
                                    <p style="color: #164e63;">Add helpful information or tips for your customers here.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'warning':
                    html = `
                        <div class="ui-block ui-block-warning" style="background: #fff7ed; border-left: 4px solid #f97316; padding: 1rem; border-radius: 0 0.5rem 0.5rem 0; margin: 1rem 0;">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <span style="color: #ea580c; font-size: 1.25rem;">⚠️</span>
                                <div>
                                    <h4 style="font-weight: 600; color: #9a3412; margin-bottom: 0.25rem;">Warning</h4>
                                    <p style="color: #7c2d12;">Important warning or caution message for customers.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'success':
                    html = `
                        <div class="ui-block ui-block-success" style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 1rem; border-radius: 0 0.5rem 0.5rem 0; margin: 1rem 0;">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <span style="color: #16a34a; font-size: 1.25rem;">✅</span>
                                <div>
                                    <h4 style="font-weight: 600; color: #166534; margin-bottom: 0.25rem;">Success</h4>
                                    <p style="color: #14532d;">Positive message or confirmation for customers.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'columns2':
                    html = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1rem 0;">
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem;">
                                <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Column 1</h4>
                                <p>Content for the first column goes here.</p>
                            </div>
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem;">
                                <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Column 2</h4>
                                <p>Content for the second column goes here.</p>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'columns3':
                    html = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin: 1rem 0;">
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📦</div>
                                <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Feature 1</h4>
                                <p style="font-size: 0.875rem; color: #6b7280;">Description</p>
                            </div>
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚀</div>
                                <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Feature 2</h4>
                                <p style="font-size: 0.875rem; color: #6b7280;">Description</p>
                            </div>
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">💎</div>
                                <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Feature 3</h4>
                                <p style="font-size: 0.875rem; color: #6b7280;">Description</p>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'imageText':
                    html = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: center; margin: 1rem 0;">
                            <div>
                                <img src="/images/placeholder.jpg" alt="Product image" style="width: 100%; border-radius: 0.5rem;">
                            </div>
                            <div>
                                <h3 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 0.75rem;">Your Title Here</h3>
                                <p style="color: #4b5563; margin-bottom: 1rem;">Describe your product features, benefits, or any important information that complements the image.</p>
                                <ul style="list-style: disc; padding-left: 1.25rem; color: #4b5563;">
                                    <li>Feature point one</li>
                                    <li>Feature point two</li>
                                    <li>Feature point three</li>
                                </ul>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'testimonial':
                    html = `
                        <div style="background: #f9fafb; border-radius: 0.75rem; padding: 1.5rem; margin: 1rem 0;">
                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="font-size: 3rem; color: #d1d5db; line-height: 1;">"</div>
                                <div>
                                    <p style="font-style: italic; color: #374151; margin-bottom: 1rem; font-size: 1.1rem;">This product exceeded my expectations. The quality is outstanding and the customer service was excellent!</p>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%;"></div>
                                        <div>
                                            <div style="font-weight: 600; color: #111827;">Customer Name</div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Verified Buyer</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'pricing':
                    html = `
                        <div style="background: white; border: 2px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin: 1rem 0; max-width: 300px;">
                            <div style="text-align: center;">
                                <h4 style="font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.875rem;">Package Name</h4>
                                <div style="font-size: 2.5rem; font-weight: bold; color: #111827; margin: 0.5rem 0;">$99</div>
                                <p style="color: #6b7280; font-size: 0.875rem;">per month</p>
                            </div>
                            <hr style="margin: 1rem 0; border-color: #e5e7eb;">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #22c55e;">✓</span> Feature included
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #22c55e;">✓</span> Another feature
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #22c55e;">✓</span> Premium support
                                </li>
                            </ul>
                        </div>
                    `;
                    break;
                    
                case 'faq':
                    html = `
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; margin: 1rem 0;">
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 0.5rem 0.5rem 0 0; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                <span>Frequently Asked Question?</span>
                                <span style="color: #6b7280;">+</span>
                            </div>
                            <div style="padding: 1rem; color: #4b5563;">
                                <p>The answer to the question goes here. Provide clear and helpful information to address common customer inquiries.</p>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'video':
                    const videoUrl = prompt('Enter YouTube or Vimeo URL:', 'https://www.youtube.com/watch?v=');
                    if (videoUrl) {
                        let embedUrl = videoUrl;
                        // Convert YouTube watch URL to embed URL
                        if (videoUrl.includes('youtube.com/watch')) {
                            const videoId = videoUrl.split('v=')[1]?.split('&')[0];
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        } else if (videoUrl.includes('youtu.be/')) {
                            const videoId = videoUrl.split('youtu.be/')[1]?.split('?')[0];
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        } else if (videoUrl.includes('vimeo.com/')) {
                            const videoId = videoUrl.split('vimeo.com/')[1]?.split('?')[0];
                            embedUrl = `https://player.vimeo.com/video/${videoId}`;
                        }
                        html = `
                            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 1rem 0; border-radius: 0.5rem;">
                                <iframe src="${embedUrl}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
                            </div>
                        `;
                    }
                    break;
                    
                case 'button':
                    html = `
                        <div style="text-align: center; margin: 1rem 0;">
                            <a href="#" style="display: inline-block; background: #2563eb; color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: background 0.2s;">
                                Button Text
                            </a>
                        </div>
                    `;
                    break;
                    
                case 'divider':
                    html = `
                        <div style="display: flex; align-items: center; margin: 2rem 0;">
                            <div style="flex: 1; height: 1px; background: #e5e7eb;"></div>
                            <div style="padding: 0 1rem; color: #9ca3af; font-size: 0.875rem;">✦</div>
                            <div style="flex: 1; height: 1px; background: #e5e7eb;"></div>
                        </div>
                    `;
                    break;
                    
                case 'specs':
                    html = `
                        <div style="margin: 1rem 0;">
                            <h4 style="font-weight: 600; margin-bottom: 0.75rem;">Specifications</h4>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem; background: #f9fafb; font-weight: 500; width: 30%;">Dimension</td>
                                        <td style="padding: 0.75rem;">10 x 5 x 3 inches</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem; background: #f9fafb; font-weight: 500;">Weight</td>
                                        <td style="padding: 0.75rem;">1.5 lbs</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem; background: #f9fafb; font-weight: 500;">Material</td>
                                        <td style="padding: 0.75rem;">Premium Quality</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; background: #f9fafb; font-weight: 500;">Warranty</td>
                                        <td style="padding: 0.75rem;">1 Year</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                    break;
                    
                case 'gallery':
                    html = `
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin: 1rem 0;">
                            <img src="/images/placeholder.jpg" alt="Gallery 1" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                            <img src="/images/placeholder.jpg" alt="Gallery 2" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                            <img src="/images/placeholder.jpg" alt="Gallery 3" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                            <img src="/images/placeholder.jpg" alt="Gallery 4" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                            <img src="/images/placeholder.jpg" alt="Gallery 5" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                            <img src="/images/placeholder.jpg" alt="Gallery 6" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.5rem;">
                        </div>
                    `;
                    break;
            }
            
            if (html) {
                this.execCommand('insertHTML', html);
                this.showBlocksPanel = false;
            }
        },
        
        // Set content programmatically (for AI integration)
        setContent(newContent) {
            this.content = newContent;
            if (this.$refs.editor) {
                this.$refs.editor.innerHTML = newContent;
            }
            if (this.$refs.sourceEditor) {
                this.$refs.sourceEditor.value = newContent;
            }
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = newContent;
            }
        },
        
        // Get content
        getContent() {
            return this.content;
        }
    }
}
