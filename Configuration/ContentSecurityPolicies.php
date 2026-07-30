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
 * The widget also injects its stylesheet as an inline `<style>` element into its
 * shadow root, which needs `style-src-elem 'unsafe-inline'`.
 */
return Map::fromEntries([
    Scope::frontend(),
    new MutationCollection(
        new Mutation(MutationMode::Set, Directive::WorkerSrc, SourceKeyword::self, SourceScheme::blob),
        new Mutation(MutationMode::Extend, Directive::StyleSrcElem, SourceKeyword::self, SourceKeyword::unsafeInline),
    ),
]);
