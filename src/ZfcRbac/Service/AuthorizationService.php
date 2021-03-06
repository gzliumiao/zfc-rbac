<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfcRbac\Service;

use Rbac\Rbac;
use ZfcRbac\Assertion\AssertionInterface;
use ZfcRbac\Exception;

/**
 * Authorization service is a simple service that internally uses Rbac to check if identity is
 * granted a permission
 *
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 */
class AuthorizationService
{
    /**
     * @var Rbac
     */
    protected $rbac;

    /**
     * @var RoleService
     */
    protected $roleService;

    /**
     * Constructor
     *
     * @param RoleService $roleService
     */
    public function __construct(RoleService $roleService)
    {
        $this->rbac        = new Rbac();
        $this->roleService = $roleService;
    }

    /**
     * Check if the permission is granted to the current identity
     *
     * @param  string                           $permission
     * @param  callable|AssertionInterface|null $assertion
     * @return bool
     * @throws Exception\InvalidArgumentException If an invalid assertion is passed
     */
    public function isGranted($permission, $assertion = null)
    {
        $roles = $this->roleService->getIdentityRoles();

        if (empty($roles)) {
            return false;
        }

        /* @var \Rbac\Role\RoleInterface $role */
        foreach ($roles as $role) {
            // If we are granted, we also check the assertion as a second-pass
            if ($this->rbac->isGranted($role, $permission)) {
                return ($assertion) ? $this->assert($assertion) : true;
            }
        }

        return false;
    }

    /**
     * @param  callable|AssertionInterface $assertion
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    protected function assert($assertion)
    {
        $identity = $this->roleService->getIdentity();

        if (is_callable($assertion)) {
            return $assertion($identity);
        } elseif ($assertion instanceof AssertionInterface) {
            return $assertion->assert($identity);
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Assertions must be callable or implement ZfcRbac\Assertion\AssertionInterface, "%s" given',
            is_object($assertion) ? get_class($assertion) : gettype($assertion)
        ));
    }
}
