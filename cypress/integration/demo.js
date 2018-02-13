const uniqueEmailTag = () => Math.floor(Math.random() * 1000000);

describe('sanity check', () => {
  it('the title should match', () => {
    cy.visit('http://localhost:8000');

    cy.title().should('include', 'Laravel');
  });
});

describe('registration and login', () => {
  let email;

  before(() => {
    // generate a unique email -- you could use something like GuerrillaEmail to gerate
    // a disposable address.
    email = `demo+${uniqueEmailTag()}@example.com`
  });


  beforeEach(() => {
    Cypress.Cookies.preserveOnce('laravel_session');
  });

  it('you can reach the registration and login pages from the homepage', () => {
    cy.visit('http://localhost:8000');

    cy.get('a[href*=login]').click();
    cy.location('pathname').should('include', 'login')
      .then(() => {
        cy.go('back');
      });

    cy.get('a[href*=register]').click();
    cy.location('pathname').should('include', 'register');
  });

  it('entering valid registration information should register me', () => {
    cy.visit('http://localhost:8000/register');

    cy.get('#name').type('Demo User');
    cy.get('#email').type(email);
    cy.get('#password').type('password');
    cy.get('#password-confirm').type('password');
    cy.get('button[type="submit"]').click().then(() => {
      cy.location('pathname').should('include', 'home');
    })
  });

  it('logging out and logging back in works', () => {
    cy.visit('http://localhost:8000/home');

    cy.get('a[href="#"].dropdown-toggle')
      .click()
      .then(() => {
        cy.get('a[href*="logout"').click()
          .then(() => {
            cy.location('pathname').should('eq', '/');
          })
      });

    cy.get('a[href*=login]').click()
      .then(() => {
        cy.get('#email').type(email);
        cy.get('#password').type('password');

        cy.get('button[type="submit"]').click()
          .then(() => {
            cy.location('pathname', 'home');
          })
    });
  });
});