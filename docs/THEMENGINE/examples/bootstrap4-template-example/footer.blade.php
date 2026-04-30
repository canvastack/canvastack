{{--
    footer.blade.php — Bootstrap 4 Footer Bar Example
    ==================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/block/footer.blade.php

    Renders the bottom footer bar with copyright information.

    Variables from $components->meta->preference:
    - meta_author    : author/company name
    - email_address  : contact email

    Bootstrap 4 specific:
    - pull-right for right-aligned copyright text (use float-end in Bootstrap 5)
--}}
<?php
$copyrights    = $components->meta->preference;

// Use preference values if set, otherwise fall back to global config
$author        = !empty($copyrights['meta_author'])   ? $copyrights['meta_author']   : canvastack_config('meta_author');
$copyright     = !empty($copyrights['meta_author'])   ? $copyrights['meta_author']   : canvastack_config('copyrights');
$email_address = !empty($copyrights['email_address']) ? $copyrights['email_address'] : canvastack_config('email');
?>

{{-- FOOTER --}}
<footer role="contentinfo">
    <div class="footer-area blury">
        {{--
            pull-right is Bootstrap 4 utility class.
            In Bootstrap 5, use float-end instead.
            DefaultAdapter::getFloatRightClass() returns 'pull-right'.
        --}}
        <span class="pull-right">
            {{-- Dynamic copyright year — set by scripts.js --}}
            <span id="copyright"></span>&nbsp;

            {{-- Copyright symbol with author tooltip --}}
            <font title="{{ $author }} &lt;{{ $email_address }}&gt;">&copy;</font>&nbsp;

            {{-- Clickable copyright link --}}
            <a href="mailto:{{ $email_address }}" target="_blank" rel="noopener">
                {{ $copyright }}
            </a>,
            {{ canvastack_config('location') }}
            {{ canvastack_config('location_abbr') }}
        </span>
    </div>
</footer>
{{-- END FOOTER --}}
