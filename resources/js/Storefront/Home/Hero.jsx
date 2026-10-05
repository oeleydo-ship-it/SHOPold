import { useCallback, useEffect, useRef, useState } from 'react';
import { ArrowRight, CheckCircle2, ChevronLeft, ChevronRight, Pause, Play, PlayCircle, RotateCcw, ShieldCheck, ShoppingBag, Star, Tag, Truck } from 'lucide-react';
import SfLink from '../lib/SfLink';
import { settingIcon } from '../lib/icons';
import { cx, safeUrl, useT } from '../lib/store';

/** Autoplaying index for sliders; pauses while a slide change is animating and restarts after a manual pick. */
function useSlider(count, interval, lock = 800) {
    const [index, setIndex] = useState(0);
    const busy = useRef(false);
    const timer = useRef(null);
    const move = useCallback((next) => {
        if (busy.current || count < 2) return;
        busy.current = true;
        setIndex((i) => (typeof next === 'function' ? next(i) : next));
        setTimeout(() => { busy.current = false; }, lock);
    }, [count, lock]);
    const start = useCallback(() => {
        clearInterval(timer.current);
        if (count > 1) timer.current = setInterval(() => move((i) => (i + 1) % count), interval);
    }, [count, interval, move]);
    useEffect(() => { start(); return () => clearInterval(timer.current); }, [start]);

    return {
        index,
        next: () => move((i) => (i + 1) % count),
        prev: () => move((i) => (i - 1 + count) % count),
        go: (i) => { if (i !== index) { move(i); start(); } },
    };
}

function useHeroText() {
    const t = useT();
    return {
        badge: `${t('new_collection', 'New Collection')} ${new Date().getFullYear()}`,
        title: t('discover_premium', 'Discover Premium Products'),
        subtitle: t('hero_subtitle', 'Shop the latest trends'),
        button: t('shop_now', 'Shop Now'),
        secondary: t('learn_more', 'Learn More'),
    };
}

const alignBox = (a) => (a === 'center' ? 'mx-auto text-center' : a === 'right' ? 'ms-auto text-end' : '');
const alignRow = (a) => (a === 'center' ? 'justify-center' : a === 'right' ? 'justify-end' : '');

function Arrows({ onPrev, onNext, className, buttonClass = 'bg-white/90 text-gray-700 hover:bg-white' }) {
    return (
        <>
            <button type="button" onClick={onPrev} aria-label="Previous slide" className={cx('absolute start-2 top-1/2 z-20 -translate-y-1/2 items-center justify-center rounded-full shadow-md backdrop-blur-sm transition sm:start-4', buttonClass, className)}><ChevronLeft size={20} className="rtl:rotate-180" /></button>
            <button type="button" onClick={onNext} aria-label="Next slide" className={cx('absolute end-2 top-1/2 z-20 -translate-y-1/2 items-center justify-center rounded-full shadow-md backdrop-blur-sm transition sm:end-4', buttonClass, className)}><ChevronRight size={20} className="rtl:rotate-180" /></button>
        </>
    );
}

function Dots({ count, index, onGo, active = 'w-6 bg-primary-600 sm:w-8', idle = 'bg-gray-400 hover:bg-gray-500' }) {
    return (
        <div className="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1.5 sm:bottom-6 sm:gap-2">
            {Array.from({ length: count }, (_, i) => (
                <button key={i} type="button" onClick={() => onGo(i)} aria-label={`Slide ${i + 1}`} aria-current={i === index}
                    className={cx('h-2 rounded-full transition-all duration-300 sm:h-3', i === index ? active : cx('w-2 sm:w-3', idle))} />
            ))}
        </div>
    );
}

function TrustLine({ className, iconClass = '' }) {
    const t = useT();
    return (
        <div className={cx('mt-4 flex-wrap items-center gap-3 text-xs sm:mt-8 sm:gap-4 sm:text-sm md:mt-12 md:gap-6', className)}>
            <span className="flex items-center gap-1.5"><Truck size={15} className={iconClass} />{t('free_shipping', 'Free Shipping')}</span>
            <span className="flex items-center gap-1.5"><ShieldCheck size={15} className={iconClass} />{t('secure_payment', 'Secure Payment')}</span>
            <span className="flex items-center gap-1.5"><RotateCcw size={15} className={iconClass} />{t('easy_returns', 'Easy Returns')}</span>
        </div>
    );
}

function PrimaryButton({ href, children, className = 'bg-primary-600 text-white hover:bg-primary-700 shadow-lg hover:-translate-y-1 hover:shadow-xl' }) {
    return (
        <SfLink href={safeUrl(href, '/shop')} className={cx('sf-btn group inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 sm:px-6 sm:py-3 sm:text-base md:px-8 md:py-4', className)}>
            <ShoppingBag size={18} />{children}
        </SfLink>
    );
}

function SecondaryButton({ href, children, show = true, className = 'border-2 border-gray-300 text-gray-700 hover:border-primary-500 hover:text-primary-600' }) {
    return (
        <SfLink href={safeUrl(href, '/about')} className={cx('sf-btn group items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 sm:px-6 sm:py-3 sm:text-base md:px-8 md:py-4', show ? 'inline-flex' : 'hidden sm:inline-flex', className)}>
            {children}<ArrowRight size={16} className="transition-transform group-hover:translate-x-1 rtl:rotate-180" />
        </SfLink>
    );
}

const IMAGE_HEIGHT = {
    small: 'min-h-[320px] sm:min-h-[380px] md:min-h-[400px] lg:min-h-[450px]',
    medium: 'min-h-[380px] sm:min-h-[450px] md:min-h-[500px] lg:min-h-[600px]',
    large: 'min-h-[450px] sm:min-h-[520px] md:min-h-[600px] lg:min-h-[700px]',
    full: 'min-h-[100svh]',
};
const BIG_HEIGHT = { auto: '', small: 'min-h-[400px] lg:min-h-[450px]', medium: 'min-h-[500px] lg:min-h-[600px]', large: 'min-h-[600px] lg:min-h-[700px]', full: 'min-h-screen' };
const MOBILE_HEIGHT = { small: 'min-h-[320px]', medium: 'min-h-[380px]', large: 'min-h-[450px]', full: 'min-h-[100svh]' };

function GradientHero({ hero, text }) {
    const Icon = settingIcon(hero.badgeIcon);
    return (
        <section className="relative overflow-hidden" style={{ backgroundColor: hero.bgColor || 'var(--sf-primary-600)' }}>
            <div className="sf-container relative z-10 py-10 sm:py-16 md:py-20 lg:py-28">
                <div className="grid items-center gap-6 sm:gap-8 lg:grid-cols-2 lg:gap-12">
                    <div className="text-center text-white lg:text-start">
                        <span className="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/20 px-3 py-1.5 text-xs font-medium sm:mb-6 sm:px-4 sm:py-2 sm:text-sm"><Icon size={14} className="text-yellow-400" />{text.badge}</span>
                        <h1 className="mb-3 text-2xl leading-tight font-bold sm:mb-4 sm:text-3xl md:mb-6 md:text-4xl lg:text-5xl xl:text-6xl">{text.title}</h1>
                        <p className="mx-auto mb-4 line-clamp-2 max-w-lg text-sm leading-relaxed text-white/80 sm:mb-6 sm:line-clamp-none sm:text-base md:mb-8 md:text-lg lg:mx-0">{text.subtitle}</p>
                        <div className="flex flex-col flex-wrap justify-center gap-2 sm:flex-row sm:gap-3 md:gap-4 lg:justify-start">
                            <PrimaryButton href={hero.buttonLink} className="bg-white text-primary-600 hover:bg-primary-50">{text.button}</PrimaryButton>
                            <SecondaryButton href={hero.secondaryLink} show={hero.mobile.secondaryButton} className="border-2 border-white text-white hover:bg-white/10">{text.secondary}</SecondaryButton>
                        </div>
                        <TrustLine className={cx('justify-center text-white/60 lg:justify-start', hero.mobile.features ? 'flex' : 'hidden sm:flex')} iconClass="text-white/80" />
                    </div>
                    <div className="hidden lg:block">
                        <div className="overflow-hidden rounded-2xl border border-white/20"><img src={hero.image} alt="" className="h-[450px] w-full object-cover" /></div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function ImageHero({ hero, text }) {
    const Icon = settingIcon(hero.badgeIcon);
    const height = IMAGE_HEIGHT[hero.height] ?? IMAGE_HEIGHT.medium;
    return (
        <section className={cx('relative', height)}>
            <img src={hero.image} alt="" className="absolute inset-0 h-full w-full object-cover" fetchPriority="high" />
            <div className={cx('sf-container relative z-10 flex items-end py-8 sm:items-center sm:py-16 md:py-24', height)} style={{ color: hero.textColor || '#1f2937' }}>
                <div className={cx('max-w-2xl pb-8 sm:pb-0', alignBox(hero.textAlign))}>
                    <span className="mb-3 inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1.5 text-xs font-medium text-primary-700 sm:mb-6 sm:px-4 sm:py-2 sm:text-sm"><Icon size={14} className="text-yellow-500" />{text.badge}</span>
                    <h1 className="mb-3 text-2xl leading-tight font-bold sm:mb-4 sm:text-3xl md:mb-6 md:text-4xl lg:text-5xl xl:text-6xl">{text.title}</h1>
                    <p className="mb-4 line-clamp-2 text-sm leading-relaxed text-gray-600 sm:mb-6 sm:line-clamp-none sm:text-base md:mb-8 md:text-lg">{text.subtitle}</p>
                    <div className={cx('flex flex-col flex-wrap gap-2 sm:flex-row sm:gap-3 md:gap-4', alignRow(hero.textAlign))}>
                        <PrimaryButton href={hero.buttonLink}>{text.button}</PrimaryButton>
                        <SecondaryButton href={hero.secondaryLink} show={hero.mobile.secondaryButton}>{text.secondary}</SecondaryButton>
                    </div>
                    <TrustLine className={cx('text-gray-500', alignRow(hero.textAlign), hero.mobile.features ? 'flex' : 'hidden sm:flex')} />
                </div>
            </div>
        </section>
    );
}

/** One slide's text block for the slider / full-width styles (badge, title, subtitle, buttons, features). */
function SlideText({ slide, text, active, fallbackColor, hero, variant }) {
    const align = slide.text_align || 'left';
    const features = [slide.feature_1, slide.feature_2, slide.feature_3].filter(Boolean);
    const showFeatures = (['1', 'true', 1, true].includes(slide.show_features) || hero.mobile.features) && features.length > 0;
    const anim = (delay) => ({ className: cx('transition-all duration-700 ease-out', active ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'), style: { transitionDelay: `${delay}ms` } });
    const full = variant === 'fullwidth';
    return (
        <div className={cx(full ? '' : 'max-w-2xl', alignBox(align))} style={{ color: slide.text_color || fallbackColor }}>
            {slide.badge && (!full || hero.fullwidth.badge) && (
                <div {...anim(100)}><span className={cx('mb-4 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-medium sm:mb-6 sm:text-sm', full ? 'border border-white/30 bg-white/20 font-semibold tracking-wider text-white uppercase backdrop-blur-sm' : 'bg-primary-100 text-primary-700')}><Star size={13} className="text-yellow-500" fill="currentColor" />{slide.badge}</span></div>
            )}
            <h1 {...anim(200)}><span className={cx('mb-3 block leading-tight font-bold sm:mb-4 md:mb-6', full ? 'text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl' : 'text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl')}>{slide.title || text.title}</span></h1>
            <div {...anim(300)}><p className="mb-4 line-clamp-2 text-sm leading-relaxed opacity-80 sm:mb-6 sm:line-clamp-none sm:text-base md:mb-8 md:text-lg">{slide.subtitle || text.subtitle}</p></div>
            <div {...anim(400)}>
                <div className={cx('flex flex-col flex-wrap gap-2 sm:flex-row sm:gap-3 md:gap-4', alignRow(align))}>
                    <PrimaryButton href={slide.button_link || '/shop'}>{slide.button_text || text.button}</PrimaryButton>
                    {slide.secondary_button_text && <SecondaryButton href={slide.secondary_button_link || '/about'} show={hero.mobile.secondaryButton} className={full ? 'border-2 border-white/80 text-white hover:bg-white/10' : 'border-2 border-current hover:bg-white/10'}>{slide.secondary_button_text}</SecondaryButton>}
                </div>
            </div>
            {showFeatures && (
                <div {...anim(500)}>
                    <div className={cx('mt-4 flex-wrap items-center gap-3 text-xs opacity-70 sm:mt-8 sm:gap-6 sm:text-sm', alignRow(align), hero.mobile.features ? 'flex' : 'hidden sm:flex')}>
                        {features.map((f) => <span key={f} className="flex items-center gap-1.5"><CheckCircle2 size={14} />{f}</span>)}
                    </div>
                </div>
            )}
        </div>
    );
}

function SliderHero({ hero, text, variant = 'slider' }) {
    const slides = hero.slides;
    const full = variant === 'fullwidth';
    const s = useSlider(slides.length, hero.interval, full ? 1000 : 800);
    const nav = hero.navigation;
    const height = full ? (BIG_HEIGHT[hero.height] ?? BIG_HEIGHT.large) : (IMAGE_HEIGHT[hero.height] ?? IMAGE_HEIGHT.medium);
    const fw = hero.fullwidth;
    const position = { center: 'items-center justify-center text-center', right: 'items-center justify-end text-end', 'bottom-left': 'items-end justify-start pb-16', 'bottom-center': 'items-end justify-center text-center pb-16' }[fw.position] ?? 'items-center justify-start';
    const widthClass = { narrow: 'max-w-md', wide: 'max-w-3xl', full: 'max-w-full' }[fw.width] ?? 'max-w-2xl';

    return (
        <section className={cx('relative overflow-hidden', height)} aria-roledescription="carousel">
            <div className="absolute inset-0">
                {slides.length > 0 ? slides.map((slide, i) => (
                    <div key={i} className={cx('absolute inset-0 transition-all ease-in-out', full ? 'duration-1000' : 'duration-[800ms]', i === s.index ? 'scale-100 opacity-100' : cx('opacity-0', !full && 'scale-105'))} aria-hidden={i !== s.index}>
                        {slide.image && <img src={slide.image} alt="" className="h-full w-full object-cover" loading={i === 0 ? 'eager' : 'lazy'} />}
                        {parseFloat(slide.overlay_opacity) > 0 && <div className="absolute inset-0 bg-black" style={{ opacity: parseFloat(slide.overlay_opacity) }} />}
                    </div>
                )) : <img src={hero.image} alt="" className="h-full w-full object-cover" />}
            </div>
            {full && fw.overlay && fw.overlayOpacity > 0 && (
                <div className="pointer-events-none absolute inset-0" style={{ background: `linear-gradient(to right, ${fw.overlayStart}, ${fw.overlayEnd} 70%)`, opacity: fw.overlayOpacity }} />
            )}
            <div className={cx('relative z-10 flex h-full', full ? position : 'items-end sm:items-center', height)}>
                <div className="sf-container w-full pb-16 sm:pb-0">
                    <div className={cx('relative', full && widthClass)}>
                        {slides.length > 0 ? slides.map((slide, i) => (
                            <div key={i} className={cx(i === s.index ? 'relative' : 'pointer-events-none absolute inset-x-0 top-0')} aria-hidden={i !== s.index}>
                                <SlideText slide={slide} text={text} active={i === s.index} hero={hero} variant={variant} fallbackColor={full ? '#ffffff' : hero.textColor || '#1f2937'} />
                            </div>
                        )) : <SlideText slide={{}} text={text} active hero={hero} variant={variant} fallbackColor={full ? '#ffffff' : hero.textColor || '#1f2937'} />}
                    </div>
                </div>
            </div>
            {slides.length > 1 && ['both', 'arrows'].includes(nav) && (
                <Arrows onPrev={s.prev} onNext={s.next}
                    className={cx('h-9 w-9 sm:h-10 sm:w-10 md:h-12 md:w-12', hero.mobile.arrows ? 'flex' : 'hidden sm:flex')}
                    buttonClass={full ? 'border border-white/20 bg-white/10 text-white hover:bg-white/20' : 'bg-white/80 text-gray-700 hover:bg-white sm:bg-white/90'} />
            )}
            {slides.length > 1 && ['both', 'dots'].includes(nav) && (full ? (
                <div className="absolute bottom-8 left-1/2 z-20 flex -translate-x-1/2 items-center gap-3">
                    {slides.map((_, i) => (
                        <button key={i} type="button" onClick={() => s.go(i)} aria-label={`Slide ${i + 1}`} aria-current={i === s.index} className="group h-1 w-12 overflow-hidden rounded-full bg-white/30">
                            <span className={cx('block h-full rounded-full bg-white transition-all duration-300', i === s.index ? 'w-full' : 'w-0 group-hover:w-1/3')} />
                        </button>
                    ))}
                </div>
            ) : <Dots count={slides.length} index={s.index} onGo={s.go} />)}
        </section>
    );
}

function ShowcaseHero({ hero, text }) {
    const t = useT();
    const items = hero.showcase.products;
    const s = useSlider(items.length, hero.interval);
    return (
        <section className="py-6 sm:py-8" style={{ backgroundColor: hero.showcase.bg }}>
            <div className="sf-container">
                <div className="relative overflow-hidden rounded-3xl" style={{ backgroundColor: hero.showcase.card }}>
                    <div className="grid min-h-[350px] sm:min-h-[400px] lg:min-h-[500px] lg:grid-cols-2">
                        <div className="relative order-2 flex flex-col justify-center p-6 sm:p-8 lg:order-1 lg:p-12">
                            {items.length > 0 ? items.map((p, i) => (
                                <div key={p.id} className={cx('flex flex-col justify-center transition-all duration-700', i === s.index ? 'translate-y-0 opacity-100' : 'pointer-events-none absolute inset-0 translate-y-6 p-6 opacity-0 sm:p-8 lg:p-12')} aria-hidden={i !== s.index}>
                                    <span className="mb-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1.5 text-xs font-semibold tracking-wider text-primary-700 uppercase">
                                        {p.discount > 0 ? <><Tag size={12} className="text-red-500" />{p.discount}% OFF</> : p.featured ? <><Star size={12} className="text-yellow-500" />{t('featured', 'Featured')}</> : t('trending', 'Trending')}
                                    </span>
                                    <h1 className="mb-4 text-2xl leading-tight font-bold text-gray-900 sm:text-3xl md:text-4xl lg:text-5xl">{p.name}</h1>
                                    <p className="mb-4 line-clamp-2 max-w-md text-sm leading-relaxed text-gray-600 sm:text-base">{p.short}</p>
                                    <div className="mb-6 flex items-center gap-3">
                                        <span className="text-3xl font-bold text-primary-600">{p.price}</span>
                                        {p.regular && <span className="text-xl text-gray-400 line-through">{p.regular}</span>}
                                    </div>
                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <PrimaryButton href={p.url}>{t('shop_now', 'Shop Now')}</PrimaryButton>
                                        <SecondaryButton href="/shop" className="border-2 border-gray-200 text-gray-700 hover:border-primary-500 hover:text-primary-600">{t('view_all', 'View All')}</SecondaryButton>
                                    </div>
                                </div>
                            )) : (
                                <>
                                    <span className="mb-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1.5 text-xs font-semibold tracking-wider text-primary-700 uppercase"><Star size={12} className="text-yellow-500" />{text.badge}</span>
                                    <h1 className="mb-4 text-2xl leading-tight font-bold text-gray-900 sm:text-3xl md:text-4xl lg:text-5xl">{text.title}</h1>
                                    <p className="mb-6 max-w-md text-sm leading-relaxed text-gray-600 sm:mb-8 sm:text-base">{text.subtitle}</p>
                                    <PrimaryButton href={hero.buttonLink}>{text.button}</PrimaryButton>
                                </>
                            )}
                            <div className="mt-6 flex flex-wrap items-center gap-4 text-xs text-gray-500 sm:mt-8 sm:gap-6 sm:text-sm">
                                <span className="flex items-center gap-1.5"><CheckCircle2 size={14} className="text-green-500" />{t('free_shipping', 'Free Shipping')}</span>
                                <span className="flex items-center gap-1.5"><CheckCircle2 size={14} className="text-green-500" />{t('secure_payment', 'Secure Payment')}</span>
                            </div>
                        </div>
                        <div className="relative order-1 flex min-h-[200px] items-center justify-center overflow-hidden p-6 sm:min-h-[250px] sm:p-8 lg:order-2 lg:min-h-full" style={{ backgroundColor: hero.showcase.imageBg }}>
                            {items.length > 0 ? items.map((p, i) => (
                                <div key={p.id} className={cx('flex items-center justify-center transition-all duration-700', i === s.index ? 'relative scale-100 opacity-100' : 'absolute inset-0 scale-90 p-6 opacity-0 sm:p-8')} aria-hidden={i !== s.index}>
                                    <img src={p.imageAny} alt={p.name} className="max-h-[180px] w-auto object-contain transition-transform duration-500 hover:scale-105 sm:max-h-[250px] lg:max-h-[400px]" />
                                </div>
                            )) : <img src={hero.image} alt="" className="max-h-[180px] w-auto object-contain sm:max-h-[250px] lg:max-h-[400px]" />}
                        </div>
                    </div>
                    {items.length > 1 && (
                        <>
                            <div className="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2.5">
                                {items.map((p, i) => (
                                    <button key={p.id} type="button" onClick={() => s.go(i)} aria-label={p.name} aria-current={i === s.index}
                                        className={cx('h-2.5 rounded-full transition-all duration-500', i === s.index ? 'w-8 bg-primary-600 shadow-md' : 'w-2.5 bg-gray-300/80 hover:bg-gray-400')} />
                                ))}
                            </div>
                            <Arrows onPrev={s.prev} onNext={s.next} className="hidden h-11 w-11 lg:flex" />
                        </>
                    )}
                </div>
            </div>
        </section>
    );
}

function VideoHero({ hero, text }) {
    const v = hero.video;
    const ref = useRef(null);
    const [playing, setPlaying] = useState(true);
    const toggle = () => { if (!ref.current) return; playing ? ref.current.pause() : ref.current.play(); setPlaying(!playing); };
    const height = BIG_HEIGHT[hero.height] ?? BIG_HEIGHT.large;
    return (
        <section className={cx('relative overflow-hidden', height)}>
            <div className="absolute inset-0">
                {v.url ? <video ref={ref} autoPlay muted={v.muted} loop={v.loop} playsInline poster={v.poster ?? hero.image} className="h-full w-full object-cover"><source src={v.url} type="video/mp4" /></video>
                    : v.embedId ? <iframe title="Hero video" src={`https://www.youtube-nocookie.com/embed/${v.embedId}?autoplay=1&mute=1&loop=1&playlist=${v.embedId}&controls=0&rel=0&modestbranding=1`} className="h-full w-full scale-150" allow="autoplay; encrypted-media" />
                        : <img src={v.poster ?? hero.image} alt="" className="h-full w-full object-cover" />}
            </div>
            <div className="absolute inset-0 bg-black/50" />
            <div className={cx('relative z-10 flex h-full items-center', height)}>
                <div className="sf-container w-full">
                    <div className={cx('max-w-2xl', alignBox(hero.textAlign))} style={{ color: hero.textColor || '#ffffff' }}>
                        <span className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/20 px-4 py-2 text-xs font-semibold tracking-wider text-white uppercase backdrop-blur-sm sm:text-sm"><PlayCircle size={14} />{text.badge}</span>
                        <h1 className="mb-4 text-3xl leading-tight font-bold sm:mb-6 sm:text-4xl md:text-5xl lg:text-6xl">{text.title}</h1>
                        <p className="mb-6 text-base leading-relaxed opacity-90 sm:mb-8 sm:text-lg lg:text-xl">{text.subtitle}</p>
                        <div className={cx('flex flex-col flex-wrap gap-3 sm:flex-row sm:gap-4', alignRow(hero.textAlign))}>
                            <PrimaryButton href={hero.buttonLink}>{text.button}</PrimaryButton>
                            <SecondaryButton href={hero.secondaryLink} className="border-2 border-white/80 text-white hover:bg-white/10">{text.secondary}</SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
            {v.controls && v.url && (
                <button type="button" onClick={toggle} aria-label={playing ? 'Pause video' : 'Play video'} className="absolute end-8 bottom-8 z-20 flex h-12 w-12 items-center justify-center rounded-full border border-white/30 bg-white/20 text-white backdrop-blur-sm hover:bg-white/30">
                    {playing ? <Pause size={18} /> : <Play size={18} />}
                </button>
            )}
        </section>
    );
}

function MobileHero({ hero }) {
    const t = useT();
    const slides = hero.mobile.slides;
    const s = useSlider(slides.length, hero.interval, 500);
    return (
        <section className={cx('relative overflow-hidden sm:hidden', MOBILE_HEIGHT[hero.mobile.height] ?? MOBILE_HEIGHT.medium)}>
            {slides.map((slide, i) => (
                <div key={i} className={cx('absolute inset-0 transition-opacity duration-700', i === s.index ? 'z-10 opacity-100' : 'z-0 opacity-0')} aria-hidden={i !== s.index}>
                    {slide.image && <img src={slide.image} alt="" className="h-full w-full object-cover" />}
                    {parseFloat(slide.overlay_opacity ?? 0.3) > 0 && <div className="absolute inset-0 bg-black" style={{ opacity: parseFloat(slide.overlay_opacity ?? 0.3) }} />}
                </div>
            ))}
            <div className="absolute inset-0 z-20 flex items-end pb-16">
                <div className="w-full px-4">
                    {slides.map((slide, i) => i === s.index && (
                        <div key={i} className={cx(slide.text_align === 'center' ? 'text-center' : slide.text_align === 'right' ? 'text-end' : '')} style={{ color: slide.text_color || '#ffffff' }}>
                            {slide.badge && <span className="mb-3 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/20 px-3 py-1.5 text-xs font-medium backdrop-blur-sm"><Star size={12} className="text-yellow-400" fill="currentColor" />{slide.badge}</span>}
                            <h1 className="mb-2 text-2xl leading-tight font-bold">{slide.title}</h1>
                            {slide.subtitle && <p className="mb-4 line-clamp-2 text-sm opacity-90">{slide.subtitle}</p>}
                            <div className={cx('flex flex-wrap gap-2', slide.text_align === 'center' ? 'justify-center' : slide.text_align === 'right' ? 'justify-end' : '')}>
                                <SfLink href={safeUrl(slide.button_link, '/shop')} className="sf-btn inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg"><ShoppingBag size={15} />{slide.button_text || t('shop_now', 'Shop Now')}</SfLink>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
            {slides.length > 1 && hero.mobile.arrows && <Arrows onPrev={s.prev} onNext={s.next} className="flex h-9 w-9" buttonClass="bg-white/80 text-gray-700" />}
            {slides.length > 1 && <Dots count={slides.length} index={s.index} onGo={s.go} active="w-6 bg-white" idle="bg-white/50" />}
        </section>
    );
}

/** Home page hero; the style is chosen in the theme customizer (Settings > Appearance > Hero). */
export default function Hero({ hero }) {
    const text = useHeroText();
    const hasMobile = hero.mobile.slides.length > 0;
    const pad = hero.transparentHeader ? 'pt-20' : '';
    const desktop = {
        gradient: <GradientHero hero={hero} text={text} />,
        image: <ImageHero hero={hero} text={text} />,
        slider: <SliderHero hero={hero} text={text} />,
        fullwidth: <SliderHero hero={hero} text={text} variant="fullwidth" />,
        product_showcase: <ShowcaseHero hero={hero} text={text} />,
        video: <VideoHero hero={hero} text={text} />,
    }[hero.style] ?? <ShowcaseHero hero={{ ...hero, showcase: { ...hero.showcase, products: [], bg: '#f9fafb', card: '#ffffff', imageBg: '#f3f4f6' } }} text={text} />;

    return (
        <div className={pad}>
            {hasMobile && <MobileHero hero={hero} />}
            <div className={hasMobile ? 'hidden sm:block' : ''}>{desktop}</div>
        </div>
    );
}
