<div class="fi-infolist-entry-wrapper">
    <textarea
        readonly
        rows="20"
        class="fi-input"
        style="display: block; width: 100%; resize: vertical; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; border-radius: 0.5rem;"
    >{{ $responseBody }}</textarea>

    <div style="margin-top: 0.5rem; font-size: 0.75rem; line-height: 1rem; opacity: 0.7;">
        {{ strlen($responseBody) }} characters
    </div>
</div>
