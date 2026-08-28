<p class="text-sm text-ink-muted">
    {!! __('loop.signup_legal_copy', [
        'terms' => '<a href="'.e(route('legal.show', 'terms')).'" target="_blank" rel="noopener" class="font-semibold text-violet">'.e(__('loop.footer_terms')).'</a>',
        'privacy' => '<a href="'.e(route('legal.show', 'privacy')).'" target="_blank" rel="noopener" class="font-semibold text-violet">'.e(__('loop.footer_privacy')).'</a>',
    ]) !!}
</p>
<label class="mt-3 flex items-start gap-3 text-sm">
    <input type="checkbox" name="marketing_opt_in" value="1" class="mt-1 rounded border-ink/20 text-violet focus:ring-violet" @checked(old('marketing_opt_in'))>
    <span>{{ __('loop.marketing_opt_in') }}</span>
</label>
