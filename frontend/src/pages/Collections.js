import React, { useState, useEffect } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Container,
  Grid,
  Typography,
  Box,
  Card,
  CardContent,
  CardMedia,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Alert,
} from '@mui/material';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';

function Collections() {
  const { isAuthenticated } = useAuth();
  const [collections, setCollections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [openDialog, setOpenDialog] = useState(false);
  const [newCollection, setNewCollection] = useState({ nom: '', description: '' });
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    const fetchCollections = async () => {
      try {
        const response = await axios.get('/collections');
        setCollections(response.data['hydra:member']);
      } catch (error) {
        setError('Erreur lors du chargement des collections');
      } finally {
        setLoading(false);
      }
    };

    if (isAuthenticated) {
      fetchCollections();
    }
  }, [isAuthenticated]);

  const handleOpenDialog = () => {
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setNewCollection({ nom: '', description: '' });
  };

  const handleCreateCollection = async () => {
    try {
      const response = await axios.post('/collections', newCollection);
      setCollections((prev) => [...prev, response.data]);
      setSuccessMessage('Collection créée avec succès');
      handleCloseDialog();
    } catch (error) {
      setError('Erreur lors de la création de la collection');
    }
  };

  const handleDeleteCollection = async (id) => {
    try {
      await axios.delete(`/collections/${id}`);
      setCollections((prev) => prev.filter((c) => c.id !== id));
      setSuccessMessage('Collection supprimée avec succès');
    } catch (error) {
      setError('Erreur lors de la suppression de la collection');
    }
  };

  if (!isAuthenticated) {
    return (
      <Container>
        <Typography>Veuillez vous connecter pour accéder à vos collections.</Typography>
      </Container>
    );
  }

  if (loading) {
    return (
      <Container>
        <Typography>Chargement...</Typography>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <Typography color="error">{error}</Typography>
      </Container>
    );
  }

  return (
    <Container>
      <Box sx={{ my: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 4 }}>
          <Typography variant="h4" component="h1">
            Mes Collections
          </Typography>
          <Button
            variant="contained"
            color="primary"
            onClick={handleOpenDialog}
          >
            Créer une collection
          </Button>
        </Box>

        <Grid container spacing={4}>
          {collections.map((collection) => (
            <Grid item key={collection.id} xs={12} sm={6} md={4}>
              <Card>
                <CardContent>
                  <Typography variant="h5" component="h2" gutterBottom>
                    {collection.nom}
                  </Typography>
                  <Typography variant="body2" color="text.secondary" paragraph>
                    {collection.description}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {collection.oeuvres?.length || 0} œuvre(s)
                  </Typography>
                  <Box sx={{ mt: 2 }}>
                    <Button
                      component={RouterLink}
                      to={`/collections/${collection.id}`}
                      variant="outlined"
                      sx={{ mr: 1 }}
                    >
                      Voir
                    </Button>
                    <Button
                      variant="outlined"
                      color="error"
                      onClick={() => handleDeleteCollection(collection.id)}
                    >
                      Supprimer
                    </Button>
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>

        <Dialog open={openDialog} onClose={handleCloseDialog}>
          <DialogTitle>Créer une nouvelle collection</DialogTitle>
          <DialogContent>
            <TextField
              autoFocus
              margin="dense"
              label="Nom"
              fullWidth
              value={newCollection.nom}
              onChange={(e) =>
                setNewCollection((prev) => ({ ...prev, nom: e.target.value }))
              }
            />
            <TextField
              margin="dense"
              label="Description"
              fullWidth
              multiline
              rows={4}
              value={newCollection.description}
              onChange={(e) =>
                setNewCollection((prev) => ({
                  ...prev,
                  description: e.target.value,
                }))
              }
            />
          </DialogContent>
          <DialogActions>
            <Button onClick={handleCloseDialog}>Annuler</Button>
            <Button
              onClick={handleCreateCollection}
              variant="contained"
              disabled={!newCollection.nom}
            >
              Créer
            </Button>
          </DialogActions>
        </Dialog>

        {successMessage && (
          <Alert
            severity="success"
            onClose={() => setSuccessMessage('')}
            sx={{ mt: 2 }}
          >
            {successMessage}
          </Alert>
        )}
      </Box>
    </Container>
  );
}

export default Collections; 