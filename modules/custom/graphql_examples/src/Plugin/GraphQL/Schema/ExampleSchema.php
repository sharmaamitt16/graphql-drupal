<?php

namespace Drupal\graphql_examples\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql_examples\Wrappers\QueryConnection;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * @Schema(
 *   id = "example",
 *   name = "Example schema"
 * )
 */
class ExampleSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addQueryFields($registry, $builder);
    $this->addArticleFields($registry, $builder);

    // Create article mutation.
    $registry->addFieldResolver('Mutation', 'createArticle',
      $builder->produce('create_article')
        ->map('data', $builder->fromArgument('data'))
    );

    // Re-usable connection type fields.
    $this->addConnectionFields('ArticleConnection', $registry, $builder);

    return $registry;
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addArticleFields(ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Article', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Article', 'title',
      $builder->compose(
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent())//,
        // $builder->produce('uppercase')
        //   ->map('string', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Article', 'author',
      $builder->compose(
        $builder->produce('entity_owner')
          ->map('entity', $builder->fromParent()),
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Article', 'created',
      $builder->compose(
        $builder->produce('entity_created')
          ->map('entity', $builder->fromParent())
      )
    );
    $registry->addFieldResolver('Article', 'changed',
      $builder->compose(
        $builder->produce('entity_changed')
          ->map('entity', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Article', 'status',
      $builder->compose(
        $builder->produce('entity_published')
          ->map('entity', $builder->fromParent())
      )
    );

    // $registry->addFieldResolver('Article', 'image',
    //   $builder->compose(
    //     $builder->produce('property_path')
    //       ->map('type', $builder->fromValue('entity:node'))
    //       ->map('value', $builder->fromParent())
    //       ->map('path', $builder->fromValue('field_image.entity')),
    //     $builder->produce("image_url")
    //       ->map('entity',$builder->fromParent())
    //   )
    // );
    // Load the image style derivative of the file.
    $registry->addFieldResolver('Article', 'image',
      $builder->compose(
        // Load file objet.
        $builder->produce('property_path')
           ->map('type', $builder->fromValue('entity:node'))
           ->map('value', $builder->fromParent())
           ->map('path', $builder->fromValue('field_image.entity')),
        // Create derivative of image.
        $builder->produce('image_derivative')
          ->map('entity', $builder->fromParent())
          ->map('style', $builder->fromValue('thumbnail')),
        // Retrieve the url of the generated image.
        $builder->produce('image_style_url')
          ->map('derivative', $builder->fromParent()),
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addQueryFields(ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'article',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['article']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'articles',
      $builder->produce('query_articles')
        ->map('offset', $builder->fromArgument('offset'))
        ->map('limit', $builder->fromArgument('limit'))
    );
  }

  /**
   * @param string $type
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addConnectionFields($type, ResolverRegistry $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver($type, 'total',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->total();
      })
    );

    $registry->addFieldResolver($type, 'items',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->items();
      })
    );
  }
}
