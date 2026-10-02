<?php

namespace ShipReady;

final class BuiltInChecks
{
    public static function all(): array
    {
        return [
            // Security Checks
            \ShipReady\Checks\Security\DebugModeCheck::class,
            \ShipReady\Checks\Security\AppKeyCheck::class,
            \ShipReady\Checks\Security\VulnerableComposerPackagesCheck::class,
            \ShipReady\Checks\Security\VulnerableNpmPackagesCheck::class,
            \ShipReady\Checks\Security\MassAssignmentCheck::class,
            \ShipReady\Checks\Security\UnguardedModelCheck::class,
            \ShipReady\Checks\Security\RequestAllMassAssignmentCheck::class,
            \ShipReady\Checks\Security\RawBladeOutputCheck::class,
            \ShipReady\Checks\Security\SqlInjectionCheck::class,
            \ShipReady\Checks\Security\UnthrottledAuthRoutesCheck::class,
            \ShipReady\Checks\Security\CsrfExclusionCheck::class,
            \ShipReady\Checks\Security\InsecureSessionCookieCheck::class,
            \ShipReady\Checks\Security\InsecureSchemeCheck::class,
            \ShipReady\Checks\Security\MissingSecurityHeadersCheck::class,
            \ShipReady\Checks\Security\CorsWildcardWithCredentialsCheck::class,
            \ShipReady\Checks\Security\ExposedDebugToolsCheck::class,
            \ShipReady\Checks\Security\ExposedSensitiveFilesCheck::class,
            \ShipReady\Checks\Security\EnvCallOutsideConfigCheck::class,
            \ShipReady\Checks\Security\HardcodedSecretsCheck::class,
            \ShipReady\Checks\Security\DangerousFunctionsCheck::class,
            \ShipReady\Checks\Security\UnvalidatedFileUploadCheck::class,
            \ShipReady\Checks\Security\MissingAuthorizationCheck::class,
            \ShipReady\Checks\Security\SanctumTokenExpiryCheck::class,
            \ShipReady\Checks\Security\WeakPasswordHashingCheck::class,
            \ShipReady\Checks\Security\UnsignedSignedRoutesCheck::class,
            \ShipReady\Checks\Security\OpenRedirectCheck::class,
            \ShipReady\Checks\Security\ApiRateLimitCheck::class,
            \ShipReady\Checks\Security\SensitiveDataLoggedCheck::class,
            \ShipReady\Checks\Security\MissingHttpsRedirectCheck::class,
            \ShipReady\Checks\Security\ContentSecurityPolicyCheck::class,
            \ShipReady\Checks\Security\ModelMissingHiddenCheck::class,
            \ShipReady\Checks\Security\TimingAttackCheck::class,

            // Performance Checks
            \ShipReady\Checks\Performance\ConfigNotCachedCheck::class,
            \ShipReady\Checks\Performance\RoutesNotCachedCheck::class,
            \ShipReady\Checks\Performance\ViewsNotCachedCheck::class,
            \ShipReady\Checks\Performance\AutoloaderNotOptimizedCheck::class,
            \ShipReady\Checks\Performance\OpcacheDisabledCheck::class,
            \ShipReady\Checks\Performance\SlowCacheDriverCheck::class,
            \ShipReady\Checks\Performance\ModelAllInLoopCheck::class,
            \ShipReady\Checks\Performance\NPlusOneCheck::class,
            \ShipReady\Checks\Performance\LazyLoadingNotPreventedCheck::class,
            \ShipReady\Checks\Performance\MissingForeignKeyIndexCheck::class,
            \ShipReady\Checks\Performance\CollectionCountCheck::class,
            \ShipReady\Checks\Performance\DebugLoggingCheck::class,
            \ShipReady\Checks\Performance\UnversionedAssetsCheck::class,
            \ShipReady\Checks\Performance\HeavyServiceProviderBootCheck::class,
            \ShipReady\Checks\Performance\FileSessionDriverCheck::class,
            \ShipReady\Checks\Performance\UnqueuedNotificationCheck::class,
            \ShipReady\Checks\Performance\UnqueuedMailableCheck::class,
            \ShipReady\Checks\Performance\SqliteInProductionCheck::class,

            // Reliability Checks
            \ShipReady\Checks\Reliability\PendingMigrationsCheck::class,
            \ShipReady\Checks\Reliability\SchedulerNotRunningCheck::class,
            \ShipReady\Checks\Reliability\QueuedJobsMissingConfigCheck::class,
            \ShipReady\Checks\Reliability\FailedJobsTableCheck::class,
            \ShipReady\Checks\Reliability\MailDriverCheck::class,
            \ShipReady\Checks\Reliability\AppEnvCheck::class,
            \ShipReady\Checks\Reliability\StorageLinkCheck::class,
            \ShipReady\Checks\Reliability\StoragePermissionsCheck::class,
            \ShipReady\Checks\Reliability\LogRotationCheck::class,
            \ShipReady\Checks\Reliability\HealthRouteCheck::class,
            \ShipReady\Checks\Reliability\MissingEnvExampleCheck::class,
            \ShipReady\Checks\Reliability\DeadRoutesCheck::class,
            \ShipReady\Checks\Reliability\TransactionWrapsHttpCheck::class,
            \ShipReady\Checks\Reliability\PhpVersionCheck::class,
            \ShipReady\Checks\Reliability\ErrorMonitoringCheck::class,
            \ShipReady\Checks\Reliability\HorizonNotConfiguredCheck::class,
            \ShipReady\Checks\Reliability\BackupNotConfiguredCheck::class,
            \ShipReady\Checks\Reliability\TrustProxiesCheck::class,
            \ShipReady\Checks\Reliability\ForeignKeyConstraintCheck::class,

            // Octane Checks
            \ShipReady\Checks\Octane\SingletonRequestCaptureCheck::class,
            \ShipReady\Checks\Octane\MutableStaticPropertyCheck::class,
            \ShipReady\Checks\Octane\OctaneMaxRequestsCheck::class,
            \ShipReady\Checks\Octane\IncompatibleOctanePackagesCheck::class,
            \ShipReady\Checks\Octane\ContainerStateLeakCheck::class,
            \ShipReady\Checks\Octane\OctaneWorkerResetCheck::class,

            // Queue Checks
            \ShipReady\Checks\Queue\RetryAfterTimeoutMismatchCheck::class,
            \ShipReady\Checks\Queue\JobHttpWithoutRetryCheck::class,
            \ShipReady\Checks\Queue\ShouldBeUniqueCacheCheck::class,
            \ShipReady\Checks\Queue\SchedulerOverlappingCheck::class,
            \ShipReady\Checks\Queue\HorizonConfigCheck::class,

            // Tenancy Checks
            \ShipReady\Checks\Tenancy\ModelTenantScopeCheck::class,
            \ShipReady\Checks\Tenancy\TenantAwareJobCheck::class,
            \ShipReady\Checks\Tenancy\TenantCachePrefixCheck::class,
            \ShipReady\Checks\Tenancy\GlobalScopeBypassCheck::class,
            \ShipReady\Checks\Tenancy\CentralRouteExposureCheck::class,
            \ShipReady\Checks\Tenancy\TenancyOctaneFlushCheck::class,

            // Package-Conditional Checks
            \ShipReady\Checks\Packages\LivewireLockedPropertyCheck::class,
            \ShipReady\Checks\Packages\LivewireUploadValidationCheck::class,
            \ShipReady\Checks\Packages\FilamentPanelAccessCheck::class,
            \ShipReady\Checks\Packages\FilamentResourcePolicyCheck::class,
            \ShipReady\Checks\Packages\WebhookSignatureVerificationCheck::class,

            // Infrastructure Checks
            \ShipReady\Checks\Infrastructure\DockerRootUserCheck::class,
            \ShipReady\Checks\Infrastructure\DockerEnvFileCheck::class,
            \ShipReady\Checks\Infrastructure\ComposerNoDevCheck::class,
            \ShipReady\Checks\Infrastructure\PhpIniExposureCheck::class,

            // Version-Specific Checks
            \ShipReady\Checks\VersionSpecific\OldCsrfMiddlewareCheck::class,
            \ShipReady\Checks\VersionSpecific\DeprecatedMiddlewareLocationCheck::class,
            \ShipReady\Checks\VersionSpecific\AiSdkKeyCheck::class,
            \ShipReady\Checks\VersionSpecific\ReverbTlsCheck::class,
            \ShipReady\Checks\VersionSpecific\DeadQueueJobCheck::class,
            \ShipReady\Checks\VersionSpecific\PasskeyConfigCheck::class,
            \ShipReady\Checks\VersionSpecific\ControllerConstructorMiddlewareCheck::class,
            \ShipReady\Checks\VersionSpecific\LegacyExceptionHandlerCheck::class,
            \ShipReady\Checks\VersionSpecific\ApiRoutesNotLoadedCheck::class,
            \ShipReady\Checks\VersionSpecific\LivewireCompatibilityCheck::class,
            \ShipReady\Checks\VersionSpecific\SanctumStatefulDomainsCheck::class,
            \ShipReady\Checks\VersionSpecific\BroadcastRoutesNotLoadedCheck::class,
            \ShipReady\Checks\VersionSpecific\FortifyTwoFactorCheck::class,
            \ShipReady\Checks\VersionSpecific\TrimStringsMiddlewareCheck::class,
            \ShipReady\Checks\VersionSpecific\InertiaCompatibilityCheck::class,
            \ShipReady\Checks\VersionSpecific\CarbonImmutableCheck::class,
        ];
    }
}
