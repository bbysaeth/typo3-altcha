<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Type\Map;

/**
 * The ALTCHA widget runs its proof-of-work in Web Workers that are created from
 * `blob:` URLs. Without an explicit `worker-src`, the browser falls back to
 * `script-src 'self'`, blocks the worker and the widget reports
 * "verification failed" before any solution is computed.
 *
 * The widget also injects its stylesheet as an inline `<style>` element, which
 * needs `style-src-elem 'unsafe-inline'`.
 *
 * Widget 3.2.0 added nonce support for that element: it copies
 * `document.currentScript.nonce`, falling back to `meta[name="csp-nonce"]`. That
 * does not help here, so do not be tempted to swap `'unsafe-inline'` for
 * `SourceKeyword::nonceProxy`:
 *
 * - TYPO3 only nonces inline assets, not `<script src="...">`, so the extension's
 *   bundle carries no nonce for the widget to copy - verified in the browser, the
 *   stylesheet ends up blocked and the widget renders unstyled.
 * - Rendering a `meta[name="csp-nonce"]` ourselves would break on cached pages,
 *   because the nonce is per request while the markup is not. TYPO3 solves that
 *   with a placeholder that is substituted on delivery, which is not available for
 *   hand-written markup.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new Mutation(MutationMode::Set, Directive::WorkerSrc, SourceKeyword::self, SourceScheme::blob),
        new Mutation(MutationMode::Extend, Directive::StyleSrcElem, SourceKeyword::self, SourceKeyword::unsafeInline),
    ),
]);
