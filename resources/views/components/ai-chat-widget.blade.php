{{-- AI Chat Widget --}}
@php
    $aiApiKey = setting('ai.api_key') ?: setting('features.ai_api_key');
    $botName = setting('ai.chat_name', 'Shop Assistant');
    $welcomeMessage = setting('ai.chat_welcome', 'Hello! How can I help you today?');
@endphp
@if(setting('features.ai_chat_enabled') && $aiApiKey)
<div id="ai-chat-widget" x-data="aiChatWidget()" x-cloak>
    {{-- Chat Toggle Button --}}
    <button @click="toggleChat()" 
            class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center z-50 group"
            :class="{ 'scale-0 pointer-events-none': isOpen }">
        <i class="fas fa-robot text-xl group-hover:scale-110 transition-transform"></i>
        <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
    </button>

    {{-- Chat Window --}}
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="fixed bottom-6 right-6 w-96 max-w-[calc(100vw-3rem)] bg-white rounded-2xl shadow-2xl z-50 overflow-hidden flex flex-col"
         style="height: 550px; max-height: calc(100vh - 6rem);">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h3 class="font-semibold">{{ $botName }}</h3>
                    <p class="text-xs text-purple-200 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span> 
                        <span x-show="isLoggedIn">Hi, <span x-text="userName"></span>!</span>
                        <span x-show="!isLoggedIn">Online • AI-Powered</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('helpdesk.create') }}" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition" title="Create Support Ticket">
                    <i class="fas fa-ticket-alt text-sm"></i>
                </a>
                <button @click="clearChat()" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition" title="New conversation">
                    <i class="fas fa-plus text-sm"></i>
                </button>
                <button @click="toggleChat()" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Messages Container --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messagesContainer">
            {{-- Loading Indicator --}}
            <div class="flex items-center justify-center py-8" x-show="isLoading">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-purple-500 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-500">Loading conversation...</p>
                </div>
            </div>

            {{-- Welcome Message --}}
            <div class="flex gap-3" x-show="!isLoading && messages.length === 0">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600 text-sm"></i>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-[85%]">
                    <p class="text-sm text-gray-700 font-medium">
                        <span x-show="isLoggedIn">Hi <span x-text="userName"></span>! 👋</span>
                        <span x-show="!isLoggedIn">{{ $welcomeMessage }}</span>
                    </p>
                    <p class="text-sm text-gray-600 mt-2">I can help you with:</p>
                    <ul class="text-sm text-gray-600 mt-1 space-y-1">
                        <li class="flex items-center gap-2"><i class="fas fa-box text-purple-500 w-4"></i> Track your orders</li>
                        <li class="flex items-center gap-2"><i class="fas fa-search text-purple-500 w-4"></i> Find products</li>
                        <li class="flex items-center gap-2"><i class="fas fa-truck text-purple-500 w-4"></i> Shipping info</li>
                        <li class="flex items-center gap-2"><i class="fas fa-undo text-purple-500 w-4"></i> Returns & refunds</li>
                        <li class="flex items-center gap-2"><i class="fas fa-tag text-purple-500 w-4"></i> Promotions & deals</li>
                    </ul>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <a href="{{ route('helpdesk.create') }}" class="flex items-center gap-2 text-sm text-red-600 hover:text-red-700 font-medium">
                            <i class="fas fa-headset"></i> Need more help? Create a support ticket
                        </a>
                    </div>
                </div>
            </div>

            {{-- Chat Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex gap-3" :class="{ 'flex-row-reverse': msg.role === 'user' }">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                         :class="msg.role === 'user' ? 'bg-primary-100' : 'bg-purple-100'">
                        <i :class="msg.role === 'user' ? 'fas fa-user text-primary-600' : 'fas fa-robot text-purple-600'" class="text-sm"></i>
                    </div>
                    <div class="rounded-2xl px-4 py-3 max-w-[85%]"
                         :class="msg.role === 'user' ? 'bg-primary-600 text-white rounded-tr-none' : 'bg-gray-100 text-gray-700 rounded-tl-none'">
                        <p class="text-sm whitespace-pre-wrap" x-html="formatMessage(msg.content)"></p>
                        
                        {{-- Product Results --}}
                        <template x-if="msg.products && msg.products.length > 0">
                            <div class="mt-3 space-y-2 border-t border-gray-200 pt-3">
                                <p class="text-xs font-medium text-gray-500">Related Products:</p>
                                <template x-for="product in msg.products" :key="product.url">
                                    <a :href="product.url" class="flex items-center gap-2 p-2 bg-white rounded-lg hover:bg-gray-50 transition text-gray-700">
                                        <i class="fas fa-shopping-bag text-purple-500"></i>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate" x-text="product.name"></p>
                                            <p class="text-xs text-gray-500" x-text="'$' + product.price"></p>
                                        </div>
                                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Typing Indicator --}}
            <div class="flex gap-3" x-show="isTyping">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600 text-sm"></i>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3">
                    <div class="flex gap-1.5">
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="px-4 py-2 border-t bg-gray-50" x-show="!isLoading && (messages.length === 0 || showQuickActions)">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
                <button @click="sendQuickMessage('What products do you recommend?')" 
                        class="flex-shrink-0 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium hover:bg-purple-200 transition flex items-center gap-1">
                    <i class="fas fa-star"></i> Recommendations
                </button>
                <button @click="showOrderTracker = true" 
                        class="flex-shrink-0 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium hover:bg-blue-200 transition flex items-center gap-1">
                    <i class="fas fa-box"></i> Track Order
                </button>
                <button @click="sendQuickMessage('What are your shipping options and delivery times?')" 
                        class="flex-shrink-0 px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-medium hover:bg-green-200 transition flex items-center gap-1">
                    <i class="fas fa-truck"></i> Shipping
                </button>
                <button @click="sendQuickMessage('What is your return and refund policy?')" 
                        class="flex-shrink-0 px-3 py-1.5 bg-orange-100 text-orange-700 rounded-full text-xs font-medium hover:bg-orange-200 transition flex items-center gap-1">
                    <i class="fas fa-undo"></i> Returns
                </button>
                <button @click="showTicketForm = true" 
                        class="flex-shrink-0 px-3 py-1.5 bg-red-100 text-red-700 rounded-full text-xs font-medium hover:bg-red-200 transition flex items-center gap-1">
                    <i class="fas fa-headset"></i> Create Ticket
                </button>
            </div>
        </div>

        {{-- Create Ticket Modal --}}
        <div x-show="showTicketForm" class="absolute inset-0 bg-white z-10 flex flex-col">
            <div class="bg-gradient-to-r from-red-600 to-pink-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="showTicketForm = false" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h3 class="font-semibold">Create Support Ticket</h3>
                </div>
            </div>
            <div class="flex-1 p-4 overflow-y-auto">
                <div class="space-y-4">
                    @auth
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                        <input type="text" x-model="ticketSubject" placeholder="Brief description of your issue" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select x-model="ticketCategory" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="order">Order Issue</option>
                            <option value="product">Product Question</option>
                            <option value="shipping">Shipping & Delivery</option>
                            <option value="payment">Payment Issue</option>
                            <option value="refund">Refund Request</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea x-model="ticketDescription" rows="4" placeholder="Please describe your issue in detail..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    </div>
                    <div class="flex items-center gap-2 p-3 bg-purple-50 rounded-lg">
                        <input type="checkbox" x-model="includeConversation" id="includeConvo" class="rounded text-purple-600">
                        <label for="includeConvo" class="text-sm text-gray-700">Include AI conversation history with ticket</label>
                    </div>
                    <button @click="createTicket()" 
                            class="w-full py-3 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-lg font-medium hover:shadow-lg transition flex items-center justify-center gap-2"
                            :disabled="!ticketSubject || !ticketDescription || isCreatingTicket">
                        <i class="fas fa-paper-plane" x-show="!isCreatingTicket"></i>
                        <i class="fas fa-spinner fa-spin" x-show="isCreatingTicket"></i>
                        <span x-text="isCreatingTicket ? 'Creating...' : 'Submit Ticket'"></span>
                    </button>
                    @else
                    <div class="text-center py-8">
                        <i class="fas fa-lock text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-600 mb-4">Please log in to create a support ticket</p>
                        <a href="{{ route('login') }}" class="inline-block px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                            <i class="fas fa-sign-in-alt mr-2"></i>Log In
                        </a>
                    </div>
                    @endauth

                    {{-- Ticket Created Success --}}
                    <div x-show="ticketCreated" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                        <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                        <p class="font-medium text-green-800">Ticket Created Successfully!</p>
                        <p class="text-sm text-green-600 mt-1">Ticket #<span x-text="createdTicketNumber"></span></p>
                        <a href="{{ route('helpdesk.index') }}" class="inline-block mt-3 px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                            View My Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Tracker Modal --}}
        <div x-show="showOrderTracker" class="absolute inset-0 bg-white z-10 flex flex-col">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="showOrderTracker = false" class="w-8 h-8 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h3 class="font-semibold">Track Your Order</h3>
                </div>
            </div>
            <div class="flex-1 p-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                        <input type="text" x-model="orderNumber" placeholder="e.g., ORD-12345" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email (optional, for verification)</label>
                        <input type="email" x-model="orderEmail" placeholder="your@email.com" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button @click="trackOrder()" 
                            class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-medium hover:shadow-lg transition flex items-center justify-center gap-2"
                            :disabled="!orderNumber || isTracking">
                        <i class="fas fa-search" x-show="!isTracking"></i>
                        <i class="fas fa-spinner fa-spin" x-show="isTracking"></i>
                        <span x-text="isTracking ? 'Searching...' : 'Track Order'"></span>
                    </button>
                    
                    {{-- Order Result --}}
                    <div x-show="orderResult" class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <template x-if="orderResult && orderResult.success">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status</span>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium"
                                          :class="{
                                              'bg-yellow-100 text-yellow-800': orderResult.order.status === 'pending',
                                              'bg-blue-100 text-blue-800': orderResult.order.status === 'processing',
                                              'bg-purple-100 text-purple-800': orderResult.order.status === 'shipped',
                                              'bg-green-100 text-green-800': orderResult.order.status === 'delivered',
                                              'bg-red-100 text-red-800': orderResult.order.status === 'cancelled'
                                          }"
                                          x-text="orderResult.order.status_label"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Order Date</span>
                                    <span class="text-sm font-medium" x-text="orderResult.order.placed_date"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Total</span>
                                    <span class="text-sm font-medium" x-text="'$' + orderResult.order.total"></span>
                                </div>
                                <template x-if="orderResult.order.tracking_number">
                                    <div class="pt-3 border-t">
                                        <p class="text-sm text-gray-600 mb-1">Tracking Number</p>
                                        <p class="font-mono text-sm bg-white px-3 py-2 rounded border" x-text="orderResult.order.tracking_number"></p>
                                    </div>
                                </template>
                                <button @click="askAboutOrder()" class="w-full mt-2 py-2 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-200 transition">
                                    <i class="fas fa-comment-dots mr-2"></i> Ask about this order
                                </button>
                            </div>
                        </template>
                        <template x-if="orderResult && !orderResult.success">
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-circle text-red-500 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-600" x-text="orderResult.message"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-4 border-t bg-white">
            <form @submit.prevent="sendMessage()" class="flex gap-2">
                <input type="text" 
                       x-model="inputMessage" 
                       placeholder="Ask me anything..." 
                       class="flex-1 px-4 py-2.5 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       :disabled="isTyping || isLoading"
                       @keydown.enter.prevent="sendMessage()">
                <button type="submit" 
                        class="w-11 h-11 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-full flex items-center justify-center hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="!inputMessage.trim() || isTyping || isLoading">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </form>
            <p class="text-xs text-gray-400 text-center mt-2">
                <i class="fas fa-shield-alt mr-1"></i> 
                <span x-show="isLoggedIn">Your conversation is saved</span>
                <span x-show="!isLoggedIn">Log in to save conversation history</span>
            </p>
        </div>
    </div>
</div>

<script>
function aiChatWidget() {
    return {
        isOpen: false,
        messages: [],
        inputMessage: '',
        isTyping: false,
        isLoading: true,
        showQuickActions: true,
        currentProduct: null,
        showOrderTracker: false,
        showTicketForm: false,
        orderNumber: '',
        orderEmail: '',
        orderResult: null,
        isTracking: false,
        currentOrderContext: null,
        sessionId: null,
        isLoggedIn: false,
        userName: '',
        ticketSubject: '',
        ticketDescription: '',
        ticketCategory: 'other',
        includeConversation: true,
        isCreatingTicket: false,
        ticketCreated: false,
        createdTicketNumber: '',
        
        init() {
            this.loadHistory();
        },
        
        async loadHistory() {
            this.isLoading = true;
            try {
                const response = await fetch('{{ route("ai.chat.history") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();
                
                if (data.success) {
                    this.isLoggedIn = data.is_logged_in || false;
                    this.userName = data.user_name || '';
                    
                    if (data.session_id) {
                        this.sessionId = data.session_id;
                        this.messages = data.messages || [];
                        this.showQuickActions = this.messages.length === 0;
                    } else {
                        this.sessionId = 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    }
                }
            } catch (error) {
                console.error('Failed to load chat history:', error);
                this.sessionId = 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            } finally {
                this.isLoading = false;
                this.detectCurrentProduct();
            }
        },

        detectCurrentProduct() {
            try {
                if (window.aiCurrentProduct && typeof window.aiCurrentProduct === 'object') {
                    this.currentProduct = window.aiCurrentProduct;
                } else {
                    this.currentProduct = null;
                }
            } catch (e) {
                this.currentProduct = null;
            }
        },
        
        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => this.scrollToBottom());
                this.maybeSendProductNotification();
            }
        },

        maybeSendProductNotification() {
            if (!this.currentProduct || !this.currentProduct.slug) return;

            const key = 'ai_chat_product_notified_' + this.currentProduct.slug;
            if (sessionStorage.getItem(key) === '1') return;

            // Only proactively notify if there isn't already recent assistant output
            const hasRecentAssistant = this.messages.slice(-3).some(m => m.role === 'assistant');
            if (hasRecentAssistant) {
                sessionStorage.setItem(key, '1');
                return;
            }

            this.sendProductNotification();
            sessionStorage.setItem(key, '1');
        },

        async sendProductNotification() {
            if (this.isTyping || this.isLoading) return;
            if (!this.currentProduct) return;

            this.isTyping = true;
            this.showQuickActions = false;

            try {
                const payload = {
                    message: 'The customer is currently viewing the product in product_context. Provide a short highlight (3-6 key features) and mention the best active coupon/promotion that could apply at checkout. Include the product link and keep it concise.',
                    history: this.messages.slice(-10).map(m => ({ role: m.role, content: m.content })),
                    product_context: this.currentProduct,
                };

                if (this.currentOrderContext) {
                    payload.order_number = this.currentOrderContext.order_number;
                    payload.email = this.orderEmail;
                }

                const response = await fetch('{{ route("ai.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                const assistantContent = data.success ? data.message : (data.message || 'Sorry, I encountered an error.');
                this.messages.push({
                    role: 'assistant',
                    content: assistantContent,
                    products: data.products || null,
                });

                this.storeMessage('assistant', assistantContent);
            } catch (error) {
                console.error('AI Product Notification Error:', error);
            } finally {
                this.isTyping = false;
                this.scrollToBottom();
            }
        },
        
        async clearChat() {
            if (confirm('Start a new conversation? Your current chat will be saved.')) {
                // End current conversation on server if logged in
                if (this.isLoggedIn && this.sessionId) {
                    try {
                        await fetch('{{ route("ai.chat.new-conversation") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (error) {
                        console.error('Failed to end conversation:', error);
                    }
                }
                
                this.messages = [];
                this.currentOrderContext = null;
                this.showQuickActions = true;
                this.sessionId = 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
        },
        
        sendQuickMessage(message) {
            this.inputMessage = message;
            this.sendMessage();
        },
        
        formatMessage(content) {
            if (content === null || content === undefined) return '';

            const escapeHtml = (str) => String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const normalizeInternalPath = (url) => {
                if (!url) return url;
                // Normalize older paths used by AI responses
                if (url.startsWith('/product/')) {
                    return '/shop/' + url.substring('/product/'.length);
                }
                if (url.startsWith('/category/')) {
                    return '/shop/category/' + url.substring('/category/'.length);
                }
                return url;
            };

            const sanitizeUrl = (url) => {
                if (!url) return '#';
                url = String(url).trim();
                if (url.startsWith('http://') || url.startsWith('https://')) {
                    return url;
                }
                if (url.startsWith('/')) {
                    return normalizeInternalPath(url);
                }
                return '#';
            };

            const buildLinkHtml = (label, url, isProduct = false) => {
                const safeUrl = sanitizeUrl(url);
                const hrefAttr = escapeHtml(safeUrl);
                const safeLabel = escapeHtml(label);
                const isExternal = safeUrl.startsWith('http://') || safeUrl.startsWith('https://');
                const targetAttr = isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
                const productClass = isProduct ? 'text-purple-600 font-semibold hover:text-purple-800 underline break-words' : 'text-purple-600 underline break-words';
                return `<a href="${hrefAttr}"${targetAttr} class="${productClass}">${safeLabel}</a>`;
            };

            let text = String(content);

            // Extract Markdown links first, store placeholders (handle bold links like **[Name](url)**)
            const mdLinks = [];
            // Handle **[text](url)** pattern (bold markdown links)
            text = text.replace(/\*\*\[([^\]]+)\]\(([^\s)]+)\)\*\*/g, (match, label, url) => {
                const idx = mdLinks.push({ label, url, bold: true }) - 1;
                return `@@MDLINK_${idx}@@`;
            });
            // Handle regular [text](url) pattern
            text = text.replace(/\[([^\]]+)\]\(([^\s)]+)\)/g, (match, label, url) => {
                const idx = mdLinks.push({ label, url, bold: false }) - 1;
                return `@@MDLINK_${idx}@@`;
            });

            // Escape the rest to prevent XSS
            text = escapeHtml(text);

            // Convert **bold** text to <strong>
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

            // Linkify plain URLs (external)
            text = text.replace(/(https?:\/\/[^\s<]+)/g, (m) => buildLinkHtml(m, m));

            // Linkify common internal paths (including /shop/...)
            text = text.replace(/(\/(?:shop|product|category|helpdesk)(?:\/[a-zA-Z0-9\-._~%!$&'()*+,;=:@]+)+)/g, (m) => {
                return buildLinkHtml(m, m, m.includes('/shop/'));
            });

            // Restore Markdown links
            mdLinks.forEach((l, idx) => {
                const token = `@@MDLINK_${idx}@@`;
                const isProductLink = l.url.includes('/shop/') || l.url.includes('/product/');
                let linkHtml = buildLinkHtml(l.label, l.url, isProductLink);
                if (l.bold) {
                    linkHtml = `<strong>${linkHtml}</strong>`;
                }
                text = text.replace(token, linkHtml);
            });

            return text;
        },

        async sendMessage() {
            const message = this.inputMessage.trim();
            if (!message || this.isTyping) return;
            
            this.messages.push({ role: 'user', content: message });
            this.inputMessage = '';
            this.isTyping = true;
            this.showQuickActions = false;
            this.scrollToBottom();
            
            // Store user message in database
            this.storeMessage('user', message);
            
            try {
                const payload = {
                    message: message,
                    history: this.messages.slice(-10).map(m => ({ role: m.role, content: m.content })),
                };

                if (this.currentProduct) {
                    payload.product_context = this.currentProduct;
                }
                
                // Include order context if available
                if (this.currentOrderContext) {
                    payload.order_number = this.currentOrderContext.order_number;
                    payload.email = this.orderEmail;
                }
                
                const response = await fetch('{{ route("ai.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                
                const data = await response.json();
                
                const assistantContent = data.success ? data.message : (data.message || 'Sorry, I encountered an error.');
                const assistantMessage = { 
                    role: 'assistant', 
                    content: assistantContent,
                    products: data.products || null,
                };
                
                this.messages.push(assistantMessage);
                
                // Store assistant message in database
                this.storeMessage('assistant', assistantContent);
                
            } catch (error) {
                console.error('AI Chat Error:', error);
                const errorMessage = 'Sorry, I\'m having trouble connecting. Please try again or contact our support team.';
                this.messages.push({ 
                    role: 'assistant', 
                    content: errorMessage 
                });
                this.storeMessage('assistant', errorMessage);
            } finally {
                this.isTyping = false;
                this.scrollToBottom();
            }
        },
        
        async trackOrder() {
            if (!this.orderNumber || this.isTracking) return;
            
            this.isTracking = true;
            this.orderResult = null;
            
            try {
                const response = await fetch('{{ route("ai.chat.order-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        order_number: this.orderNumber,
                        email: this.orderEmail || null,
                    }),
                });
                
                this.orderResult = await response.json();
                
                if (this.orderResult.success) {
                    this.currentOrderContext = this.orderResult.order;
                }
                
            } catch (error) {
                console.error('Order tracking error:', error);
                this.orderResult = { success: false, message: 'Unable to track order. Please try again.' };
            } finally {
                this.isTracking = false;
            }
        },
        
        askAboutOrder() {
            this.showOrderTracker = false;
            this.inputMessage = `I have a question about my order #${this.orderNumber}`;
            this.sendMessage();
        },
        
        async storeMessage(role, content) {
            try {
                await fetch('{{ route("ai.chat.store-message") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        role: role,
                        content: content,
                    }),
                });
            } catch (error) {
                console.error('Failed to store message:', error);
            }
        },
        
        async createTicket() {
            if (!this.ticketSubject || !this.ticketDescription || this.isCreatingTicket) return;
            
            this.isCreatingTicket = true;
            this.ticketCreated = false;
            
            try {
                // Build description with conversation if requested
                let description = this.ticketDescription;
                if (this.includeConversation && this.messages.length > 0) {
                    description += '\n\n--- AI Conversation History ---\n';
                    this.messages.forEach(msg => {
                        const role = msg.role === 'user' ? 'Customer' : 'AI';
                        description += `\n[${role}]: ${msg.content}\n`;
                    });
                }
                
                const response = await fetch('{{ route("ai.chat.create-ticket") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        subject: this.ticketSubject,
                        description: description,
                        category: this.ticketCategory,
                        priority: 'medium',
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.ticketCreated = true;
                    this.createdTicketNumber = data.ticket_number;
                    this.ticketSubject = '';
                    this.ticketDescription = '';
                } else {
                    alert(data.message || 'Failed to create ticket. Please try again.');
                }
            } catch (error) {
                console.error('Ticket creation error:', error);
                alert('Failed to create ticket. Please try again.');
            } finally {
                this.isCreatingTicket = false;
            }
        },
        
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    }
}
</script>

<style>
#ai-chat-widget .scrollbar-hide::-webkit-scrollbar { display: none; }
#ai-chat-widget .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endif
