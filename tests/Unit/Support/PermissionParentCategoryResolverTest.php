<?php

namespace Tests\Unit\Support;

use App\Support\Permissions\PermissionParentCategoryResolver;
use PHPUnit\Framework\TestCase;

class PermissionParentCategoryResolverTest extends TestCase
{
    public function test_it_resolves_known_child_groups_to_parent_category(): void
    {
        $this->assertSame('fields', PermissionParentCategoryResolver::resolve('field'));
        $this->assertSame('collections', PermissionParentCategoryResolver::resolve('regular_loan_collection'));
        $this->assertSame(
            'pending_collections',
            PermissionParentCategoryResolver::resolve('pending_saving_collection')
        );
    }

    public function test_it_falls_back_to_others_for_unknown_group(): void
    {
        $this->assertSame(
            PermissionParentCategoryResolver::DEFAULT_PARENT_CATEGORY,
            PermissionParentCategoryResolver::resolve('unknown_group')
        );
        $this->assertSame(
            PermissionParentCategoryResolver::DEFAULT_PARENT_CATEGORY,
            PermissionParentCategoryResolver::resolve(null)
        );
    }

    public function test_it_accepts_and_normalizes_explicit_parent_category(): void
    {
        $this->assertSame(
            'client_registration_accounts',
            PermissionParentCategoryResolver::resolve('any_group', 'Client Registration Accounts')
        );
    }
}
