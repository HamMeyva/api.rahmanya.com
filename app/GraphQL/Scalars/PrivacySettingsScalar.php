<?php declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

final class PrivacySettingsScalar extends ScalarType
{
    /** @var string */
    public string $name = 'PrivacySettings';

    /** @var string|null */
    public ?string $description = 'User privacy settings as a JSON object';

    /** Serializes an internal value to include in a response. */
    public function serialize(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (is_array($value)) {
            return $value;
        }

        return [
            'profile_visibility' => 'public',
            'show_following' => true,
            'show_followers' => true,
            'allow_tagging' => true,
            'allow_comments' => true,
            'comment_privacy' => 'everyone',
            'tag_privacy' => 'everyone',
        ];
    }

    /** Parses an externally provided value (query variable) to use as an input. */
    public function parseValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            throw new Error('Invalid privacy settings JSON string');
        }

        if (is_array($value)) {
            return $value;
        }

        throw new Error('Privacy settings must be a JSON string or array');
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param  \GraphQL\Language\AST\Node  $valueNode
     * @param  array<string, mixed>|null  $variables
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        if ($valueNode instanceof StringValueNode) {
            $decoded = json_decode($valueNode->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        throw new Error('Privacy settings must be a valid JSON string');
    }
}
