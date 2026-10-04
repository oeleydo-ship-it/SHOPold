{{-- Product page: Reviews list, AI review summary and the review form. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/reviews.blade.php --}}
@module('reviews')
    <!-- Reviews Section -->
    <div class="bg-white rounded-2xl shadow-sm mt-8 p-6 lg:p-8">
        <h3 class="text-xl font-bold mb-6">{{ __('messages.customer_reviews') }}</h3>
        
        <!-- AI Review Analysis -->
        @if(isset($aiReviewAnalysis) && $aiReviewAnalysis)
        <div class="mb-8 p-5 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-100">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-robot text-purple-600"></i>
                <h4 class="font-semibold text-purple-900">AI Review Analysis</h4>
                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Beta</span>
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                @if(isset($aiReviewAnalysis['overall_sentiment']))
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center 
                        {{ $aiReviewAnalysis['overall_sentiment'] === 'positive' ? 'bg-green-100' : ($aiReviewAnalysis['overall_sentiment'] === 'negative' ? 'bg-red-100' : 'bg-yellow-100') }}">
                        <i class="fas {{ $aiReviewAnalysis['overall_sentiment'] === 'positive' ? 'fa-smile text-green-600' : ($aiReviewAnalysis['overall_sentiment'] === 'negative' ? 'fa-frown text-red-600' : 'fa-meh text-yellow-600') }}"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Overall Sentiment</p>
                        <p class="font-semibold capitalize {{ $aiReviewAnalysis['overall_sentiment'] === 'positive' ? 'text-green-700' : ($aiReviewAnalysis['overall_sentiment'] === 'negative' ? 'text-red-700' : 'text-yellow-700') }}">
                            {{ $aiReviewAnalysis['overall_sentiment'] }}
                        </p>
                    </div>
                </div>
                @endif
                
                @if(isset($aiReviewAnalysis['recommendation_rate']))
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-thumbs-up text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Would Recommend</p>
                        <p class="font-semibold text-blue-700">{{ $aiReviewAnalysis['recommendation_rate'] }}%</p>
                    </div>
                </div>
                @endif
            </div>
            
            @if(isset($aiReviewAnalysis['summary']))
            <p class="mt-4 text-sm text-gray-700 italic">"{{ $aiReviewAnalysis['summary'] }}"</p>
            @endif
            
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                @if(isset($aiReviewAnalysis['common_pros']) && count($aiReviewAnalysis['common_pros']) > 0)
                <div>
                    <p class="text-sm font-medium text-green-700 mb-2"><i class="fas fa-plus-circle mr-1"></i> Pros</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach(array_slice($aiReviewAnalysis['common_pros'], 0, 3) as $pro)
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-500 mt-0.5 text-xs"></i>
                            {{ $pro }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                @if(isset($aiReviewAnalysis['common_cons']) && count($aiReviewAnalysis['common_cons']) > 0)
                <div>
                    <p class="text-sm font-medium text-red-700 mb-2"><i class="fas fa-minus-circle mr-1"></i> Cons</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach(array_slice($aiReviewAnalysis['common_cons'], 0, 3) as $con)
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times text-red-500 mt-0.5 text-xs"></i>
                            {{ $con }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif
        
        @auth
        <form action="{{ route('shop.review', $product) }}" method="POST" class="mb-8 p-6 bg-gray-50 rounded-xl">
            @csrf
            <h4 class="font-semibold mb-4">Write a Review</h4>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select name="rating" required class="px-4 py-2 border rounded-lg">
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Terrible</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title (Optional)</label>
                <input type="text" name="title" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                <textarea name="comment" rows="4" required class="w-full px-4 py-2 border rounded-lg"></textarea>
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                Submit Review
            </button>
        </form>
        @endauth

        @if($reviews->count() > 0)
            <div class="space-y-6">
                @foreach($reviews as $review)
                <div class="border-b pb-6">
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400 text-sm">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star{{ $i <= $review->rating ? '' : ' text-gray-300' }}"></i>
                            @endfor
                        </div>
                        <span class="ml-2 font-semibold">{{ $review->title }}</span>
                    </div>
                    <p class="text-gray-600 mb-2">{{ $review->comment }}</p>
                    <p class="text-sm text-gray-500">By {{ $review->user->name }} on {{ $review->created_at->format('M d, Y') }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $reviews->links() }}</div>
        @else
            <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
        @endif
    </div>
@endmodule
