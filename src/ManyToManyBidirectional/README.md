# Bidirectional manyToMany

ManyToMany relationship is a shortcut for `LeftEntity <=> manyToOne <=> JoinEntity <=> oneToMany <=> RightEntity`.

You don't have to create JoinEntity: `LeftEntity <=> manyToMany <=> RightEntity`.

# External documentation

* [Official documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/association-mapping.html#many-to-many-bidirectional)
* [Yaml config reference](https://gist.github.com/mnapoli/3839501#file-reference-yml-L74)

# Code explanations

In this example, we have a `User` entity with a manyToMany bidirectional relationship with `Credential`.

* [User.php](Entity/User.php)
* [User mapping](Config/DoctrineExamples.ManyToManyBidirectional.Entity.User.dcm.yml)

* [Credential.php](Entity/Credential.php)
* [Credential mapping](Config/DoctrineExamples.ManyToManyBidirectional.Entity.Credential.dcm.yml)

The aim of `User::$credentials` is to contains all linked Credential and vice versa. 
So when we add or remove a Credential from an User, we need to update `User::$credentials` `and` `Credential::$users`.

You can see use cases in [UserTest.php](../../tests/ManyToManyBidirectional/UserTest.php).

## Owning side: User::$credentials

### Call graph

To be sure `Users::$credentials` and `Credential::$users` have same data, we need to do that:
* $user->addCredential($credential)
* Store `$credential` into `User::$credentials`
* Store `$user` into `Credential::$users`

### Save data

As Doctrine compute changeset from owning side `only`, you can use `EntityManager::flush($user)` and everything will be saved:
* updated User / Credential
* new User / Credential
* remove links (in join table) between User and Credential who are removed.

### Mapping

```yaml
DoctrineExamples\ManyToManyBidirectional\Entity\User:
    type: entity
    table: manyToMany_user

    id:
        id:
            # Read more (in french) about identifier mapping here
            # http://steevan-barboyon.blogspot.com/2016/04/creer-un-identifiant-dentite-doctrine.html
            type: integer
            generator:
                strategy: AUTO
            options:
                unsigned: true

    manyToMany:
        credentials:
            targetEntity: DoctrineExamples\ManyToManyBidirectional\Entity\Credential
            # User is defined as owning side by defining Credential::$users as inverse side
            inversedBy: users
            cascade: [persist]
            # This is not required, it's only to rename join table to avoid conflicts between examples
            joinTable:
                name: manyToMany_user_credential
```

### Initialize User::$credentials

```php
class User
{
    /**
     * Type it as Collection not ArrayCollection because Doctrine will define it as PersistentCollection for lazy loading
     * @var Collection
     */
    private $credentials;

    public function __construct()
    {
        $this->credentials = new ArrayCollection();
    }
}
```

### User::setCredentials(): self

```php
// iterable to accept Collection, ArrayCollection, PersistentCollection, array or \Traversable instance
public function setCredentials(iterable $credentials): self
{
    // Clear all credential links before adding new ones
    $this->clearCredentials();

    // phpdoc for PHPStorm auto-completion
    /** @var Credential $credential */
    foreach ($credentials as $credential) {
        $this->addCredential($credential);
    }

    return $this;
}
```

### User::addCredential(Credential $credential): self

```php
public function addCredential(Credential $credential): self
{
    // Add $credential only if it does not already exists
    if ($this->credentials->contains($credential) === false) {

        // Add $credential into User::$credentials collection
        $this->credentials->add($credential);

        // Tell Credential to add this User in it's Credential::$users collection
        $credential->addUser($this);
    }

    return $this;
}
```

### User::getCredentials(): Collection

```php
// Credential[] for PHPStorm auto completion
/** @return Credential[]|Collection */
public function getCredentials(): Collection
{
    return $this->credentials;
}
```

### User::removeCredential(Credential $credential): self

```php
public function removeCredential(Credential $credential): self
{
    // Remove it only if it exists
    if ($this->credentials->contains($credential)) {
        $this->credentials->removeElement($credential);

        // Tell Credential to remove this User in Credential::$users
        $credential->removeUser($this);
    }

    return $this;
}
```

## Inverse side: Credential::$users

### Call graph

To be sure `Credential::$users` and `Users::$credentials` have same data, we need to do that:
* $credential->addUser($user)
* Store `$user` into `Credential::$users`
* Store `$credential` into `User::$credentials`

### Save data

As Doctrine compute changeset from owning side `only`, you `can't` use `EntityManager::flush($credential)`:
* updated User / Credential will be saved
* new User will be saved
* removed links (in join table) between User and Credential who are removed will `NOT` be saved.

Example of why you should never use EntityManager::flush($credential):
```php
$user = new User();
$credential = (new Credential())
    ->addUser($user);

// You can flush $credential: User know it, so Doctrine will find $credential and insert it into your DB
$entityManager->flush($credential);

// Now try to remove $user into $credential.
$credential->removeUser($user);

// As Doctrine will not try to compute changesets from $credential because it's the inverse side,
// Doctrine will not understand that you have removed $user into $credential, so flush() will do nothing.
// Before and after flush(), your objects $user and $credential are well fulfilled: $user is not linked to $credential
// But doctrine has not removed the row in join table.
$entityManager->flush($credential);

// Clear the entity manager to redo SQL query to retrieve real data who are in database 
$entityManager->clear();
$freshCredential = $entityManager->getRepository(Credential::class)->find($id);
// You could expect $freshCredential->getUsers() to be empty, but that's not the case!
var_dump($freshCredential->getUsers()->count()); // 1
```

So you have to flush everything, or if you can, just what you need (hardest):
```php
$user = new User();
$credential = (new Credential())
    ->addUser($user);

// You can flush $credential: User know it, so Doctrine will find $credential and insert it into your DB
$entityManager->flush($credential);

// Now try to remove $user into $credential.
$credential->removeUser($user);

// Will do nothing...
$entityManager->flush($credential);
// So flush everything...
$entityManager->flush();
// Or flush $user
 $entityManager->flush($user); // will remove the row in join table
```

### Mapping

```yaml
DoctrineExamples\ManyToManyBidirectional\Entity\Credential:
    type: entity
    table: manyToMany_credential

    id:
        id:
            # Read more (in french) about identifier mapping here
            # http://steevan-barboyon.blogspot.com/2016/04/creer-un-identifiant-dentite-doctrine.html
            type: integer
            generator:
                strategy: AUTO
            options:
                unsigned: true

    manyToMany:
        users:
            targetEntity: DoctrineExamples\ManyToManyBidirectional\Entity\User
            # Credential is defined as inverse side by defining User::$credentials as owning side
            mappedBy: credentials
            cascade: [persist]
```

### Initialize User::$credentials

```php
class Credential
{
    /**
     * Type it as Collection not ArrayCollection because Doctrine will define it as PersistentCollection for lazy loading
     * @var Collection
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}
```

### Accessors

All accessors have same code philosophy as User accessors for `User::$credentials`.
