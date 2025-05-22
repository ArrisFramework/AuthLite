<?php

namespace Arris\AuthLite;

use Arris\AuthLite\Enum\Permissions as PermissionsEnum;

class Admin
{
    private Config $config;

    private ?\PDO $pdo;
    private ?\Redis $redis;

    /**
     * @param Config $config
     * @param \Redis|null $redis
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;

        $this->pdo = $config->pdo;
        $this->redis = $config->redis;
    }

    /**
     * @param string $login
     * @param string $password
     * @param array $permissions
     * @param bool $isAdmin
     * @return int
     */
    public function createUser(
        string $login,
        string $password,
        array $permissions = []
    ): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->config->getTable('users')} (login, password_hash, permissions_mask)
            VALUES (?, ?, ?)
        ");
        $permissionsMask = $this->calculatePermissionsMask($permissions);
        $stmt->execute([
            $login,
            password_hash($password, $this->config->get('password_hash_algo')),
            $permissionsMask
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->config->getTable('users')} WHERE id = ?");
        $this->invalidateCache($userId);
        return $stmt->execute([$userId]);
    }

    /**
     * @param int $userId
     * @param array|int $permissions
     * @return bool
     */
    public function updateUserPermissions(int $userId, array|int $permissions): bool
    {
        $mask
            = is_array($permissions)
            ? $this->calculatePermissionsMask($permissions)
            : $permissions;

        $stmt = $this->pdo->prepare("UPDATE {$this->config->getTable('users')} SET permissions_mask = ? WHERE id = ?");
        $this->invalidateCache($userId);
        return $stmt->execute([$mask, $userId]);
    }

    /**
     * @param int $userId
     * @param array $permissions
     * @return bool
     */
    public function addPermissions(int $userId, array $permissions): bool
    {
        $currentMask = $this->getUserPermissionsMask($userId);
        $newMask = $currentMask | $this->calculatePermissionsMask($permissions);
        return $this->updateUserPermissions($userId, $newMask);
    }

    /**
     * @param int $userId
     * @param array $permissions
     * @return bool
     */
    public function removePermissions(int $userId, array $permissions): bool
    {
        $currentMask = $this->getUserPermissionsMask($userId);
        $newMask = $currentMask & ~$this->calculatePermissionsMask($permissions);
        return $this->updateUserPermissions($userId, $newMask);
    }

    /**
     * @param int $userId
     * @return int
     */
    private function getUserPermissionsMask(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT permissions_mask FROM {$this->config->getTable('users')} WHERE id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @param int $userId
     * @return void
     * @throws \RedisException
     */
    private function invalidateCache(int $userId): void
    {
        if ($this->redis) {
            $this->redis->del("user:{$userId}:data");
        }
    }

    /**
     * @param array $permissions
     * @return int
     */
    private function calculatePermissionsMask(array $permissions): int
    {
        $mask = 0;
        foreach ($permissions as $permission) {
            if (defined(PermissionsEnum::class . "::{$permission}")) {
                $mask |= constant(PermissionsEnum::class . "::{$permission}");
            }
        }
        return $mask;
    }


}